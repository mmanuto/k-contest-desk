<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;
use Cake\Core\Configure;

Configure::load('constants');

/**
 * Results Controller
 *
 * @method \App\Model\Entity\Result[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ResultsController extends ApiController

{
    
    /**
     * AZIONE 1: "Cosa devo mostrare?"
     * Angular chiama questo endpoint per sapere in che stato
     * si trova la categoria e cosa deve visualizzare.
     * Gestisce anche la creazione iniziale dei record di punteggio.
     * * @param string $categorycode_id L'ID della categoria (es. "KIA20")
     */
    public function getCategoryState()
    {
        $request = $this->request->getData();

        // 1. Trova il record di stato della categoria
        $stateRecord = $this->_findStateRecord($request['categorycode_id']);
        if (!$stateRecord) {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] =  'Nessun atleta iscritto a questa categoria.';
        }

        $current_phase = $stateRecord->current_phase;
        $data = null;

        // 2. Controlla se lo stato deve avanzare
        // (es. da JUDGING_PANEL ad AWAITING_BRACKETS)
        $current_phase = $this->_advanceCategoryPhase($stateRecord);

        // 3. In base alla fase (potenzialmente aggiornata), recupera i dati
        switch ($current_phase) {
            case Configure::read('PHASE_JUDGING_PANEL'):
            case Configure::read('PHASE_AWAITING_TIEBREAK'):
                $data = $this->_getPanelData($request['categorycode_id']);
                break;
            case Configure::read('PHASE_AWAITING_BRACKETS'):
                $data = $this->_getPanelData($request['categorycode_id'], true);
                break;
            case Configure::read('PHASE_BRACKETS'):
                $data = $this->getBracket($request['categorycode_id']);
                break;
            case Configure::read('PHASE_FINALIZED'):
                $data = $this->_getFinalRankingData($request['categorycode_id']);
                break;
        }

        // 4. Restituisci tutto ad Angular
        $this->apiResponse['success'] = true;
        $this->apiResponse['message'] =  'Stato recuperato.';
        $this->apiResponse['data'] = [
            'status' => $stateRecord->status,     // Lo stato UI (es. CAT_DOING)
            'phase' => $current_phase,          // Lo stato logico (es. JUDGING_PANEL)
            'athleteList' => $data                     // I dati da mostrare
        ];
    }

    /**
     * AZIONE 2: "Salva questo punteggio"
     * * Riceve 5 punteggi, calcola il totale escludendo min/max e salva.
     * Chiamato da Angular ogni volta che un giudice di sedia conferma un punteggio.
     */
    public function saveJudgeScore()
    {
        $this->loadModel('ResultsJudgedPanel');
        $data = $this->request->getData();

        $id = $data['id']; // ID della riga in results_judged_panel
        
        try {
            $scoreRecord = $this->ResultsJudgedPanel->get($id);

            $referee_scores = [
                (float)$data['referee_1'],
                (float)$data['referee_2'],
                (float)$data['referee_3'],
                (float)$data['referee_4'],
                (float)$data['referee_5']
            ];

            // Calcola il punteggio
            $calculatedData = $this->calculateKataScore($referee_scores);

            // Applica i dati calcolati e quelli ricevuti
            $patchData = array_merge($data, $calculatedData);

            $scoreRecord = $this->ResultsJudgedPanel->patchEntity($scoreRecord, $patchData);

            if ($this->ResultsJudgedPanel->save($scoreRecord)) {
                $this->apiResponse['success'] = true;
                $this->apiResponse['message'] =  'Punteggio salvato.';
                $this->apiResponse['data'] = $scoreRecord;
            } else {
                $this->apiResponse['success'] = false;
                $this->apiResponse['message'] =  'Errore di validazione.';
                $this->apiResponse['errors'] = $scoreRecord->getErrors();
            }
        } catch (\Exception $e) {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] =  'Record non trovato o errore nel salvataggio.';
        }
    }

    /**
     * AZIONE 3: "Genera Tabelloni"
     * * Chiamato dall'admin quando le qualifiche sono finite (stato 'ready_for_brackets').
     * Applica le regole Top 4 / Top 8, controlla i pareggi e crea i tabelloni.
     */
    public function generateBrackets()
    {
        $this->request->allowMethod(['post']);
        $categorycode_id = $this->request->getData('categorycode_id');

        $this->loadModel('TatamiAssignments');
        $this->loadModel('ResultsJudgedPanel');
        $this->loadModel('ResultsMatch');

        $stateRecord = $this->_findStateRecord($categorycode_id);
        
        // 1. Controlla se siamo nella fase giusta
        if ($stateRecord->current_phase != Configure::read('PHASE_AWAITING_BRACKETS')) {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] =  'Non è possibile generare i tabelloni in questa fase.';
            return;
        }

        // 2. Recupera la classifica e il numero di atleti
        $top_scores = $this->getQualificationRanking($categorycode_id);
        $athlete_count = count($top_scores);

        // 3. Applica le regole (Top 4 / Top 8)
        list($num_qualificati, $round_da_creare, $accoppiamenti_rank) = $this->_getBracketRules($athlete_count);

        /*
        // 4. Controlla i pareggi
        $score_ultimo_qualificato = $top_scores[$num_qualificati - 1]->best_score;
        $score_primo_escluso = isset($top_scores[$num_qualificati]) ? $top_scores[$num_qualificati]->best_score : null;

        if ($score_primo_escluso !== null && $score_ultimo_qualificato == $score_primo_escluso) {
            // PAREGGIO! Aggiorna la fase e avvisa il frontend
            $this->_updatePhase($stateRecord, self::PHASE_AWAITING_TIEBREAK);
            $tied_athlete_ids = $this->_findTiedAthletes(...); // (come da codice precedente)
            
            return $this->jsonResponse(false, 'Pareggio rilevato!', [
                'status' => 'tie_detected',
                'tied_athletes' => $tied_athlete_ids
            ]);
        }*/

        // 5. NESSUN PAREGGIO: Genera i tabelloni
        $atleti_qualificati = [];
        for ($i = 0; $i < $num_qualificati; $i++) {
            $atleti_qualificati[$i + 1] = $top_scores[$i]->athlete_inscription_id; // Array [ranking => id]
        }
        
        $nuoviIncontri = [];
        foreach ($accoppiamenti_rank as $acc) {
            $nuoviIncontri[] = $this->ResultsMatch->newEntity([
                'categorycode_id' => $categorycode_id,
                'round' => $round_da_creare,
                'match_number' => $acc['match'],
                'athlete_aka_inscription_id' => $atleti_qualificati[ $acc['aka_rank'] ],
                'athlete_ao_inscription_id' => $atleti_qualificati[ $acc['ao_rank'] ]
            ]);
        }

        // 6. Salva in transazione
        try {

            if (!$this->ResultsMatch->saveMany($nuoviIncontri)) {
                throw new \Exception('Impossibile salvare gli incontri nel tabellone.');
            }
            
            $this->_updatePhase($stateRecord, Configure::read('PHASE_BRACKETS'));

            $this->apiResponse['success'] = true;
            $this->apiResponse['message'] =  'Tabellone generato con successo!';
        } catch (\Exception $e) {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] =  'Errore generazione tabellone: ' . $e->getMessage();
        }
    }


    // --- FUNZIONI HELPER PRIVATE ---

    /**
     * Calcola il punteggio KATA (min/max rimossi, somma 3 centrali).
     * @param array $scores Array di 5 punteggi float
     * @return array Dati calcolati
     */
    private function calculateKataScore(array $scores): array
    {
        $filtered_scores = array_filter($scores, 'is_numeric'); // Rimuovi eventuali NULL
        if (count($filtered_scores) < 5) {
             // Non calcoliamo se non abbiamo 5 punteggi
            return [
                'min' => null, 'max' => null, 'total_score' => null,
                'valid_1' => null, 'valid_2' => null, 'valid_3' => null
            ];
        }

        sort($filtered_scores); // Ordina
        $min = array_shift($filtered_scores); // Rimuove il primo (min)
        $max = array_pop($filtered_scores);   // Rimuove l'ultimo (max)
        
        $total = array_sum($filtered_scores); // Somma i 3 rimanenti
        
        return [
            'min' => $min,
            'max' => $max,
            'valid_1' => $filtered_scores[0], // Il primo dei 3 centrali
            'valid_2' => $filtered_scores[1], // Il secondo
            'valid_3' => $filtered_scores[2], // Il terzo
            'total_score' => $total
        ];
    }

    /**
     * Recupera la classifica (ranking) dalle qualifiche
     */
    private function getQualificationRanking($categorycode_id)
    {
        $this->loadModel('ResultsJudgedPanel');

        return $this->ResultsJudgedPanel->find()
            ->where(['categorycode_id' => $categorycode_id])
            ->order(['total_score' => 'DESC'])
            ->toArray();
/*
            ->select([
                'athlete_inscription_id',
                'best_score' => $query->func()->max('total_score') // Prende il punteggio migliore
            ])
            ->where(['ResultsJudgedPanel.categorycode_id' => $categorycode_id])
            ->group(['ResultsJudgedPanel.athlete_inscription_id'])
            ->order(['best_score' => 'DESC', 'athlete_inscription_id' => 'ASC'])
            ->toList();
            */
    }
    
    /**
     * Trova gli ID degli atleti in pareggio
     */
    private function findTiedAthletes($panelTable, $competition_id, $categorycode_id, $score)
    {
         return $panelTable->find()
            ->distinct(['athlete_inscription_id'])
            ->where([
                'categorycode_id' => $categorycode_id,
                'total_score' => $score
            ])
            ->matching('AthleteInscriptions', function ($q) use ($competition_id) {
                return $q->where(['AthleteInscriptions.competition_id' => $competition_id]);
            })
            ->extract('athlete_inscription_id')
            ->toList();
    }
    
    /**
     * Finalizza una categoria con 1-3 atleti
     */
    private function _finalizeScoreOnlyCategory($panel_entries)
    {
        $this->loadModel('AthleteInscriptions');

        // Ordina gli atleti in base al total_score (già calcolato)
        usort($panel_entries, function($a, $b) {
            return $b->total_score <=> $a->total_score;
        });
        $inscriptionsTable = $this->getTableLocator()->get('AthleteInscriptions');
        $connection = $inscriptionsTable->getConnection();

        try {
            $connection->transactional(function () use ($panel_entries, $inscriptionsTable) {
                foreach ($panel_entries as $index => $entry) {
                    $ranking = $index + 1; // 1°, 2°, 3°
                    $inscription = $this->AthleteInscriptions->get($entry->athlete_inscription_id);
                    $inscription->final_ranking = $ranking;
                    $inscriptionsTable->save($inscription);
                }
            });
        } catch (\Exception $e) {
            Log::error('Errore finalizzazione gara a punteggio: ' . $e->getMessage());
        }
    }

     /**
     * Trova o crea il record di stato per una categoria.
     */
    private function _findStateRecord($categorycode_id)
    {
        $this->loadModel('TatamiAssignments');

        $stateRecord = $this->TatamiAssignments->find()
            ->where(['categorycode_id' => $categorycode_id])
            ->first();

        return $stateRecord;
    }


    /**
     * Controlla se la fase può avanzare e la aggiorna.
     * Questa è la "State Machine".
     * @return string La fase (nuova o vecchia)
     */
    private function _advanceCategoryPhase($stateRecord)
    {

        $this->loadModel('TatamiAssignments');
        $this->loadModel('ResultsJudgedPanel');
        $this->loadModel('AthleteInscriptions');

        $phase = $stateRecord->current_phase;
        $categorycode_id = $stateRecord->categorycode_id;
        $user_id = $stateRecord->user_id;

        switch ($phase) {
            case Configure::read('PHASE_PENDING'):
               
                $panel_scores_count = $this->ResultsJudgedPanel->find()
                ->where([
                    'categorycode_id' => $categorycode_id
                ])->count();

                if($panel_scores_count == 0){

                    $athletes = $this->AthleteInscriptions->find()
                    ->where(['categorycode_id' => $categorycode_id, 'deleted' => 0])
                    ->toArray();

                    foreach ($athletes as $athlete) {
                        $newEntitiy = $this->ResultsJudgedPanel->newEntity([
                                'athlete_inscription_id' => $athlete->id,
                                'user_id' => $user_id,
                                'categorycode_id' => $categorycode_id,                                
                                'round' => Configure::read('ROUND_KATA_QUALIFICA')
                            ]);
                        $this->ResultsJudgedPanel->save($newEntitiy);                    
                    }
                }
                return $this->_updatePhase($stateRecord, Configure::read('PHASE_JUDGING_PANEL'));

            
            case Configure::read('PHASE_JUDGING_PANEL'):
                
                $athlete_count = $this->AthleteInscriptions->find()
                    ->where(['categorycode_id' => $categorycode_id, 'deleted' => 0])
                    ->count();
                
                // Controlla se *tutti* gli atleti (per round 1) hanno un punteggio
                $panel_scores_count = $this->ResultsJudgedPanel->find()
                    ->where([
                        'categorycode_id' => $categorycode_id,
                        'round' => Configure::read('ROUND_KATA_QUALIFICA'),
                        'total_score IS NOT NULL'
                    ])
                    ->count();
                
                if ($athlete_count == $panel_scores_count) {
                // Tutti i punteggi ci sono!

                try {
                    // 1. Prendi la classifica (usa la funzione helper esistente)
                    //    Questo ci dà l'elenco ordinato per punteggio (il migliore per primo)
                    $top_scores = $this->getQualificationRanking($categorycode_id);

                    // 2. Calcola DENSE_RANK in PHP
                    $rank = 0;

                    foreach ($top_scores as $atleta) {
                        $rank++;
                        // Assegna il rank all'atleta
                        $atleta->pool_ranking = $rank;
                        $this->ResultsJudgedPanel->save($atleta);
                    }
                    
                } catch (\Exception $e) {

                    // Non avanzare di stato se il ranking fallisce, resta in JUDGING_PANEL
                    return $phase; 
                }


                // Ora che i ranking sono salvati, procediamo con l'avanzamento di stato

                    if ($athlete_count <= 3) {
                        // Caso 1-3 atleti: Gara finita.
                        $panel_entries = $this->ResultsJudgedPanel->find('all', [
                            'contain' => ['AthleteInscriptions' =>['Athletes']]
                        ])
                        ->where(['categorycode_id' => $categorycode_id])
                        ->toArray();

                        $this->_finalizeScoreOnlyCategory($panel_entries); // (come da codice precedente)
                        return $this->_updatePhase($stateRecord, Configure::read('PHASE_FINALIZED'));
                    } else {
                        // Caso 4+ atleti: Pronti per i tabelloni
                        return $this->_updatePhase($stateRecord, Configure::read('PHASE_AWAITING_BRACKETS'));
                    }
                }
                
                break;
            
            default:
                return $phase; // Nessun avanzamento, restituisci la fase attuale
        }

        return $phase;
    }

    /**
     * Helper sicuro per aggiornare la fase e salvarla
     */
    private function _updatePhase($stateRecord, $new_phase)
    {
        $this->loadModel('TatamiAssignments');

        $stateRecord->current_phase = $new_phase;
        $this->TatamiAssignments->save($stateRecord);
        return $new_phase;
    }

    /**
     * Restituisce le regole del tabellone in base al numero di atleti
     */
    private function _getBracketRules($athlete_count)
    {
        if ($athlete_count >= 4 && $athlete_count <= 7) {
            $num_qualificati = 4;
            $round_da_creare = 2; // Assumendo: 3=Semi, 4=Finale
            $accoppiamenti_rank = [
                ['match' => 1, 'aka_rank' => 1, 'ao_rank' => 3],
                ['match' => 2, 'aka_rank' => 2, 'ao_rank' => 4]
            ];
            return [$num_qualificati, $round_da_creare, $accoppiamenti_rank];
        } 
        
        if ($athlete_count >= 8) {
            $num_qualificati = 8;
            $round_da_creare = 3; // Assumendo: 2=Quarti, 3=Semi, 4=Finale
            $accoppiamenti_rank = [
                ['match' => 1, 'aka_rank' => 1, 'ao_rank' => 5],
                ['match' => 2, 'aka_rank' => 2, 'ao_rank' => 6],
                ['match' => 3, 'aka_rank' => 3, 'ao_rank' => 7],
                ['match' => 4, 'aka_rank' => 4, 'ao_rank' => 8]
            ];
            return [$num_qualificati, $round_da_creare, $accoppiamenti_rank];
        }
        
        return [0, 0, []]; // Caso non valido
    }

    private function _getPanelData($categorycode_id, $ranking = false){


        $this->loadModel('ResultsJudgedPanel');

        if($ranking){
            return $this->ResultsJudgedPanel->find('all', [
            'contain' => ['AthleteInscriptions' =>['Athletes' => ['Clubs']]] // Assumendo associazioni per i nomi
        ])
        ->where(['ResultsJudgedPanel.categorycode_id' => $categorycode_id])
        ->order(['pool_ranking' => 'ASC'])
        ->toArray();
        }

        return $this->ResultsJudgedPanel->find('all', [
            'contain' => ['AthleteInscriptions' =>['Athletes' => ['Clubs']]] // Assumendo associazioni per i nomi
        ])
        ->where(['ResultsJudgedPanel.categorycode_id' => $categorycode_id])
        ->toArray();
    

    }

    /**
     * Action per fornire i dati del tabellone raggruppati per Angular.
     * * @param string $categorycode_id ID della categoria
     */
    private function getBracket($categorycode_id)
    {
        $this->loadModel('ResultsMatch');
        $this->loadModel('AthleteInscriptions');

        // Carica tutti gli incontri per quella categoria, ordinati per turno
        $matches = $this->ResultsMatch->find()
            ->where(['ResultsMatch.categorycode_id' => $categorycode_id])
            //->contain(['AthleteInscriptions' => ['Athletes']])
            ->order(['round' => 'ASC', 'match_number' => 'ASC'])
            ->all();

        // Raggruppa i risultati per "round"
        $grouped = [];
        foreach ($matches as $match) {
            $match->athlete_aka_inscription = $this->AthleteInscriptions->find()->where(['AthleteInscriptions.id' => $match->athlete_aka_inscription_id])->contain(['Athletes']);
            $match->athlete_ao_inscription = $this->AthleteInscriptions->find()->where(['AthleteInscriptions.id' => $match->athlete_ao_inscription_id])->contain(['Athletes']);
            $grouped[$match->round][] = $match;
        }
        
        // Mappatura per i titoli (puoi renderla più elegante)
        $roundTitles = [
            1 => 'Quarti di Finale',
            2 => 'Semifinali',
            3 => 'Finale',
            4 => 'Ripescaggio' // Adatta ai tuoi round
        ];

        // Prepara l'output per Angular (come definito nell'interfaccia MatchData)
        $output = [];
        foreach ($grouped as $round_num => $match_list) {
            $output[] = [
                'round' => $round_num,
                'round_title' => $roundTitles[$round_num] ?? "Turno $round_num",
                'matches' => $match_list // $match_list contiene già i dati formattati
            ];
        }

        return $output;

        
    }


}
