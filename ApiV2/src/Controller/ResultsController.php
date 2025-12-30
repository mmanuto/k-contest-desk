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
     * Angular chiama questo endpoint per sapere in che stato si trova la categoria e cosa deve visualizzare.
     * Gestisce anche la creazione iniziale dei record di punteggio.
     * * @param string $categorycode_id L'ID della categoria (es. "KIA20")
     */
    public function getCategoryState()
    {
        $this->loadModel('TatamiAssignments');

        $request = $this->request->getData();
        $categorycode_id = $request['categorycode_id'];

        // 1. Recupera il record
        $stateRecord = $this->TatamiAssignments->find()
            ->where(['categorycode_id' => $categorycode_id])
            ->first();

        if (!$stateRecord) {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] =  'Nessun atleta iscritto a questa categoria.';
        }

        // 2. Esegui tutta la logica di controllo/avanzamento in un colpo solo
        $current_phase = $this->_checkAndAdvancePhase($stateRecord);
        $data = null;

        // 3. Switch dei dati in base alla fase
        switch ($current_phase) {
            // Fasi KATA (Punteggio)
            case Configure::read('PHASE_JUDGING_PANEL'):
            case Configure::read('PHASE_AWAITING_TIEBREAK'):
            case Configure::read('JUDGING_FINALIZING'):
                $data = $this->_getPanelData($categorycode_id);
                break;
            
            // Fase PERCORSO (Tempo) -- NUOVO --
            case Configure::read('PHASE_TIME_PANEL'):
            case Configure::read('TIMED_FINALIZING'):
                $data = $this->_getTimedData($categorycode_id);
                break;
            
            // Fase PRE-TABELLONE (Comune)
            case Configure::read('PHASE_AWAITING_BRACKETS'):
                
                // Se è Kata, mostriamo la classifica finale delle qualifiche
                if (!$this->_isKumite($categorycode_id)) {
                    $data = $this->_getPanelData($categorycode_id, true); // true = ordinato per rank
                } else {
                    // Se è Kumite, mostriamo la lista iscritti pronta per il sorteggio
                    $data = $this->_getRegisteredAthletes($categorycode_id);
                }
                break;

            // Fase TABELLONE (Comune)
            case Configure::read('PHASE_BRACKETS'):
            case Configure::read('BRACKETS_FINALIZING'):
                $data = $this->getBracket($categorycode_id);
                break;

            // Fase FINALE (Comune)
            case Configure::read('PHASE_FINALIZED'):
                $data = $this->_getFinalRankingData($categorycode_id);
                break;
        }

        // 4. Restituisci tutto ad Angular
        $this->apiResponse['success'] = true;
        $this->apiResponse['message'] =  'Stato recuperato.';
        $this->apiResponse['data'] = [
            'status' => $stateRecord->status,                                   // Lo stato UI (es. CAT_DOING)
            'phase' => $current_phase,                                          // Lo stato logico (es. JUDGING_PANEL)
            'is_kumite' => $this->_isKumite($categorycode_id), // Utile per il frontend
            'is_percorso' => $this->_isPercorso($categorycode_id),
            'athleteList' => $data                                              // I dati da mostrare
        ];
    }

    /**
     *   Salva Punteggio (Solo KATA)
     *   Riceve 5 punteggi, calcola il totale escludendo min/max e salva.
     *   Chiamato da Angular ogni volta che un giudice di sedia conferma un punteggio.
     */
    public function saveJudgeScore()
    {
        $this->loadModel('ResultsJudgedPanel');
        $data = $this->request->getData();

        $id = $data['id']; // ID della riga in results_judged_panel
        
        try {
            $scoreRecord = $this->ResultsJudgedPanel->get($id);           

            // Calcola il punteggio
            $calculatedData = $this->calculateKataScore($data);

            // Applica i dati calcolati e quelli ricevuti
            $patchData = array_merge($data, $calculatedData);

            $scoreRecord = $this->ResultsJudgedPanel->patchEntity($scoreRecord, $patchData);

            if ($this->ResultsJudgedPanel->save($scoreRecord)) {

                // CONTROLLO AVANZAMENTO: Dopo ogni salvataggio, controlliamo se la fase deve avanzare
                $this->_checkAndAdvancePhase($data['categorycode_id']);

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
     * AZIONE 5: Salva Tempo Percorso (Solo per fase PHASE_TIME_PANEL)
     */
    public function saveTimedScore()
    {
        $this->request->allowMethod(['post']);
        $this->loadModel('ResultsTimed');
        $this->loadModel('TatamiAssignments');

        $data = $this->request->getData();
        
        // ID della riga in results_timed (passato dal frontend)
        // Se non lo hai nel frontend, puoi cercarlo con athlete_inscription_id + categorycode_id
        $id = $data['id'] ?? null; 

        if (!$id) {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] = 'ID record mancante.';
            return;
        }

        try {
            $timeRecord = $this->ResultsTimed->get($id);

            // 1. Validazione Dati
            $minutes = (int)($data['minutes'] ?? 0);
            $seconds = (int)($data['seconds'] ?? 0);
            $milliseconds = (int)($data['milliseconds'] ?? 0);
            $penalties = (int)($data['penalties'] ?? 0); // Numero di penalità

            // 2. Calcolo Tempo Totale in Millisecondi
            // Formula: (min * 60 * 1000) + (sec * 1000) + ms + (penalità * valore_penalità)
            // Assumiamo che 1 penalità = 1000ms (1 secondo) o altro valore da config
            $penaltyValueMs = 1000; // Esempio: 1 secondo a penalità
            
            $rawTimeMs = ($minutes * 60 * 1000) + ($seconds * 1000) + $milliseconds;
            $penaltyMs = $penalties * $penaltyValueMs;
            
            $totalTimeMs = $rawTimeMs + $penaltyMs;

            // 3. Prepara i dati per il salvataggio
            $patchData = [
                'minutes' => $minutes,
                'seconds' => $seconds,
                'milliseconds' => $milliseconds,
                'penalties' => $penalties,
                'total_time' => $totalTimeMs // Questo campo serve per l'ordinamento in classifica
            ];

            $timeRecord = $this->ResultsTimed->patchEntity($timeRecord, $patchData);

            if ($this->ResultsTimed->save($timeRecord)) {
                
                // 4. CONTROLLO AVANZAMENTO AUTOMATICO
                // (Se tutti hanno fatto il percorso -> classifica -> fine)
                $categorycode_id = $data['categorycode_id'];
                $this->_checkAndAdvancePhase($this->_findStateRecord($categorycode_id));

                $this->apiResponse['success'] = true;
                $this->apiResponse['message'] = 'Tempo salvato con successo.';
                $this->apiResponse['data'] = $timeRecord;
            } else {
                $this->apiResponse['success'] = false;
                $this->apiResponse['message'] = 'Errore di validazione.';
                $this->apiResponse['errors'] = $timeRecord->getErrors();
            }

        } catch (\Exception $e) {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] = 'Errore salvataggio tempo: ' . $e->getMessage();
        }
    }

    /**
     * AZIONE 3: Genera Tabelloni (Unificata KATA + KUMITE)
     * * Chiamato dall'admin quando le qualifiche sono finite (stato 'ready_for_brackets').
     * Applica le regole Top 4 / Top 8, controlla i pareggi e crea i tabelloni.
     */
    public function generateBrackets($categorycode_id = null)
    {
        $this->request->allowMethod(['post']);
        
        $categorycode_id = $this->request->getData('categorycode_id');
        
        if($this->generateInternalBrackets(($categorycode_id))){
            $this->apiResponse['success'] = true;
            $this->apiResponse['message'] = 'Tabellone generato con successo!';
        }else{
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] = 'Errore nella generazione del tabellone';
        }      
    }

    private function generateInternalBrackets($categorycode_id)
    {

        $this->loadModel('TatamiAssignments');
        $this->loadModel('ResultsJudgedPanel');
        $this->loadModel('ResultsMatch');
        $this->loadModel('Categorycodes');

        $stateRecord = $this->_findStateRecord($categorycode_id);

        // 1. Recupera la lista atleti (ORDINATA) in base al tipo di gara
        $atleti_ids = []; // Array semplice di ID: [101, 102, 103...]

        if ($this->_isKumite($categorycode_id)) {
            // KUMITE: Prende tutti gli iscritti (Logica Seed o Random)
            $atleti_obj = $this->_getRegisteredAthletes($categorycode_id); 
            $atleti_ids = array_column($atleti_obj, 'id');
            
        } else {
            // KATA: Prende la classifica dalle qualifiche
            $top_scores = $this->getQualificationRanking($categorycode_id);
            
            // Logica Cut-off (Top 4 o Top 8)
            $total_athletes = count($top_scores);
      

            $category = $this->Categorycodes->get($categorycode_id);
            $limit = 4;

            if($category->grado == 'Marrone/Nera' && $total_athletes > 8){

                    $limit = 8;
            }
            
            //TODO: da controllare. Controllo Pareggi (solo Kata)
            //if ($this->_checkTies($top_scores, $limit, $categorycode_id, $stateRecord)) {
            //    return; // _checkTies gestisce la risposta e cambia stato
            //}
            
            // Estrai solo i primi N qualificati
            for ($i = 0; $i < $limit; $i++) {
                $atleti_ids[] = $top_scores[$i]->athlete_inscription_id;
            }
        }

        $count = count($atleti_ids);
        $nuoviIncontri = [];
        
        // --- LOGICA GENERAZIONE TABELLONE ---
        // CASO A: Girone all'Italiana (3 Atleti - Solo Kumite)
        // (Il Kata a 3 atleti è gestito via punteggi, non arriva qui)
        if ($count == 3 && $this->_isKumite($categorycode_id)) {
             $nuoviIncontri = $this->_generateRoundRobinMatches($categorycode_id, $atleti_ids);
        }
        // CASO B: Eliminazione Diretta (4, 8, 16, 32... Atleti)
        else {
            $nuoviIncontri = $this->_generateEliminationBracket($categorycode_id, $atleti_ids);
        }

        // Salvataggio
        try {
            if ($this->ResultsMatch->saveMany($nuoviIncontri)) {

                // POST-PROCESSING: Gestisci i BYE e fa avanzare chi passa il turno
                foreach ($nuoviIncontri as $match) {
        
                    // Se il match è stato creato già vinto (BYE)
                    if ($match->method_of_win === 'BYE' && !empty($match->winner_inscription_id)) {
                        $this->_advanceWinnerToNextRound($match);
                    }
                }
                $this->_updatePhase($stateRecord, Configure::read('PHASE_BRACKETS'));
            }else{
                throw new \Exception('Salvataggio incontri fallito.');
            }
            
            
           return true;
        } catch (\Exception $e) {
            return false;
        }        
    }

    /**
     * AZIONE 4: Salva Risultato Incontro (Kumite o Kata Eliminatorie)
     */
    public function saveMatchResult()
    {
        $this->request->allowMethod(['post']);
        $this->loadModel('ResultsMatch');
        $this->loadModel('TatamiAssignments');

        $data = $this->request->getData();
        $matchId = $data['id'];

        // 1. Recupera l'incontro
        $match = $this->ResultsMatch->get($matchId);

        // 2. Aggiorna i dati (punteggi, penalità, vincitore)
        $match = $this->ResultsMatch->patchEntity($match, $data);

        // Se il frontend non manda il vincitore esplicito, calcolalo qui
        if ($match->winner_inscription_id == null && isset($data['score_aka']) && isset($data['score_ao'])) {
            
            $scoreAka = $data['score_aka'];
            $scoreAo = $data['score_ao'];

            if ($scoreAka > $scoreAo) {
                $match->winner_inscription_id = $match->athlete_aka_inscription_id;
            } elseif ($scoreAo > $scoreAka) {
                $match->winner_inscription_id = $match->athlete_ao_inscription_id;
            } 

        }

        
        // if ($match->score_aka > $match->score_ao) ...

        if ($this->ResultsMatch->save($match)) {
            
            // --- LOGICA DI AVANZAMENTO ---
            
            // Se c'è un vincitore, dobbiamo gestire le conseguenze
            if ($match->winner_inscription_id) {

                // CASO GIRONE ALL'ITALIANA
                if ($match->round == Configure::read('ROUND_ROBIN')) {
                    
                    // Controlla se tutti i match del girone sono finiti
                    $finishedCount = $this->ResultsMatch->find()
                        ->where([
                            'categorycode_id' => $match->categorycode_id, 
                            'round' => Configure::read('ROUND_ROBIN'),
                            'winner_inscription_id IS NOT NULL'
                        ])->count();
                    
                    // Se ne abbiamo finiti 3, calcola classifica e chiudi
                    
                    if ($finishedCount >= 3) {
                        //$this->_finalizeRoundRobinRanking($match->categorycode_id);
                        // Aggiorna stato categoria a FINALIZED
                        //$this->_updatePhase($stateRecord, Configure::read('PHASE_FINALIZED'));

                         // Recupera lo stato attuale per aggiornarlo se serve
                        $stateRecord = $this->_findStateRecord($data['categorycode_id']);
                        $this->_updatePhase($stateRecord, Configure::read('BRACKETS_FINALIZING'));
                    }
                    
                }else{

                    // NON ERA LA FINALE -> SPOSTA IL VINCITORE AL TURNO DOPO
                    if ($match->round != Configure::read('ROUND_MATCH_FINALE')) {                  

                        $this->_advanceWinnerToNextRound($match);
                    }else{
                        // Recupera lo stato attuale per aggiornarlo se serve
                        $stateRecord = $this->_findStateRecord($data['categorycode_id']);

                        $this->_updatePhase($stateRecord, Configure::read('BRACKETS_FINALIZING'));
                    }
                }                
            }

            $this->apiResponse['success'] = true;
            $this->apiResponse['message'] = 'Incontro salvato.';
        } else {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] = 'Errore nel salvataggio.';
            $this->apiResponse['errors'] = $match->getErrors();
        }
    }

    public function getKumiteFinalRanking(){


        $this->loadModel('AthleteInscriptions');

        $data = $this->request->getData();

        // Recupera lo stato attuale per aggiornarlo se serve
        $stateRecord = $this->_findStateRecord($data['categorycode_id']);


        $athlete_count = $this->AthleteInscriptions->find()
                ->where(['categorycode_id' => $data['categorycode_id'], 'deleted' => 0])
                ->count();

    
        if($athlete_count == 3){
            $this->_finalizeRoundRobinRanking($data['categorycode_id']);
        }else{
            // 1. Scrivi i risultati nella tabella athlete_inscriptions (1°, 2°, 3°)
            $this->_finalizeBracketRanking($data['categorycode_id']);
        }        
                    
        // 2. Chiudi la categoria
        $this->_updatePhase($stateRecord, Configure::read('PHASE_FINALIZED'));

    }
    
    // ========================================================================
    // PRIVATE HELPER METHODS (Il Motore)
    // ========================================================================

    /**
     * Controlla se una categoria è di tipo Kumite.
     * (Assumi di avere una tabella CategoryCodes con colonna 'type' o simile)
     */
    private function _isKumite($categorycode_id)
    {
        return (strpos($categorycode_id, 'KU') !== false); 
    }

    /**
     * Controlla se una categoria è di tipo Percorso.
     * (Assumi di avere una tabella CategoryCodes con colonna 'type' o simile)
     */
    private function _isPercorso($categorycode_id)
    {
        return (strpos($categorycode_id, 'PER') !== false); 
    }

    /**
     * Calcola la classifica per il percorso (Tempo minore = Migliore)
     */
    private function _calculateTimedRankings($categorycode_id) {
        $this->loadModel('ResultsTimed');
        
        $results = $this->ResultsTimed->find()
            ->where(['categorycode_id' => $categorycode_id, 'total_time IS NOT NULL'])
            ->order(['total_time' => 'ASC']) // ASC: Tempo minore vince
            ->toArray();

        $rank = 0;
        foreach ($results as $res) {
            $rank++; // Qui puoi implementare logica Dense Rank se vuoi gestiore pari merito
            $res->pool_ranking = $rank; // Assumi che ResultsTimed abbia colonna 'rank' o 'pool_ranking'
            $this->ResultsTimed->save($res);
        }
    }

    /**
     * Copia la classifica finale in AthleteInscriptions
     */
    public function finalizeTimedCategory() {
        $this->loadModel('ResultsTimed');
        $this->loadModel('AthleteInscriptions');
        $this->loadModel('TatamiAssignments');

        $data = $this->request->getData();

        $results = $this->ResultsTimed->find()
            ->where(['categorycode_id' => $data['categorycode_id']])
            ->all();

        foreach ($results as $res) {
            $inscription = $this->AthleteInscriptions->get($res->athlete_inscription_id);
            $inscription->final_ranking = $res->pool_ranking; // O pool_ranking
            $this->AthleteInscriptions->save($inscription);
        }

        $stateRecord = $this->TatamiAssignments->find()
            ->where(['categorycode_id' => $data['categorycode_id']])
            ->first();

        $this->_updatePhase($stateRecord, Configure::read('PHASE_FINALIZED'));
        $this->apiResponse['success'] = true;
        $this->apiResponse['message'] = 'Classifica stilata';

    }

    /**
     * Gestisce il ciclo di vita della categoria:
     * 1. Inizializzazione (se PENDING)
     * 2. Avanzamento fase (se JUDGING e voti completi)
     * * @return string La fase corrente (potenzialmente aggiornata)
     */
    private function _checkAndAdvancePhase($stateRecord)
    {
        $this->loadModel('TatamiAssignments');
        $this->loadModel('AthleteInscriptions');
        $this->loadModel('ResultsJudgedPanel');
        $this->loadModel('ResultsTimed');

        $categorycode_id = $stateRecord->categorycode_id;
        $current_phase = $stateRecord->current_phase;

        // --- LOGICA 1: INIZIALIZZAZIONE (Bootstrap) ---
        if ($current_phase == Configure::read('PHASE_PENDING')) {
            
            // KUMITE: Non serve nulla, vai subito all'attesa
            if ($this->_isKumite($categorycode_id)) {
                if($this->generateInternalBrackets($categorycode_id)){
                    return Configure::read('PHASE_BRACKETS');
                }
                
            } // CASO B: PERCORSO (Nuova Logica)
            elseif ($this->_isPercorso($categorycode_id)) {
                
                // Popolamento tabella TEMPI (ResultsTimed)
                $countExisting = $this->ResultsTimed->find()
                    ->where(['categorycode_id' => $categorycode_id])
                    ->count();

                if ($countExisting == 0) {
                    $athletes = $this->AthleteInscriptions->find()
                        ->where(['categorycode_id' => $categorycode_id, 'deleted' => 0])
                        ->all();

                    $entitiesToSave = [];
                    foreach ($athletes as $athlete) {
                        $entitiesToSave[] = $this->ResultsTimed->newEntity([
                            'athlete_inscription_id' => $athlete->id,
                            'user_id' => $stateRecord->user_id,
                            'categorycode_id' => $categorycode_id,
                            'minutes' => 0, 
                            'seconds' => 0, 
                            'milliseconds' => 0,
                            'total_time' => null // Null finché non gareggia
                        ]);
                    }
                    
                    if (!empty($entitiesToSave)) {
                        $this->ResultsTimed->saveMany($entitiesToSave);
                    }
                }
                
                // Passa alla fase cronometro
                return $this->_updatePhase($stateRecord, Configure::read('PHASE_TIME_PANEL'));
            }
            
            // KATA: Popolamento iniziale
            else {
                // Controllo di sicurezza (idempotenza)
                $countExisting = $this->ResultsJudgedPanel->find()
                    ->where(['categorycode_id' => $categorycode_id])
                    ->count();

                if ($countExisting == 0) {
                    $athletes = $this->AthleteInscriptions->find()
                        ->where(['categorycode_id' => $categorycode_id, 'deleted' => 0])
                        ->all();

                    $entitiesToSave = [];
                    foreach ($athletes as $athlete) {
                        $entitiesToSave[] = $this->ResultsJudgedPanel->newEntity([
                            'athlete_inscription_id' => $athlete->id,
                            'user_id' => $stateRecord->user_id,
                            'categorycode_id' => $categorycode_id,                                
                            'round' => Configure::read('ROUND_KATA_QUALIFICA'),
                            'total_score' => null
                        ]);
                    }
                    
                    if (!empty($entitiesToSave)) {
                        $this->ResultsJudgedPanel->saveMany($entitiesToSave);
                    }
                }
                
                // Passa alla fase di giudizio
                return $this->_updatePhase($stateRecord, Configure::read('PHASE_JUDGING_PANEL'));
            }
        }

        // --- LOGICA 2: AVANZAMENTO (Check fine turno) ---
        // (Eseguita solo se siamo già in JUDGING_PANEL)
        if ($current_phase == Configure::read('PHASE_JUDGING_PANEL')) {
            
            // Se è Kumite, non dovrebbe essere qui, ma per sicurezza ritorniamo la fase attuale
            if ($this->_isKumite($categorycode_id)) return $current_phase;

            $athlete_count = $this->AthleteInscriptions->find()
                ->where(['categorycode_id' => $categorycode_id, 'deleted' => 0])
                ->count();
            
            $scored_count = $this->ResultsJudgedPanel->find()
                ->where([
                    'categorycode_id' => $categorycode_id, 
                    'round' => Configure::read('ROUND_KATA_QUALIFICA'), 
                    'total_score IS NOT NULL'
                ])
                ->count();

            // Se tutti hanno votato
            if ($athlete_count > 0 && $athlete_count == $scored_count) {
                
                // 1. Calcola Classifica (Dense Rank)
                $this->_calculateAndSaveRankings($categorycode_id);

                return $this->_updatePhase($stateRecord, Configure::read('JUDGING_FINALIZING'));
            }
        }

        // --- LOGICA 3: AVANZAMENTO PERCORSO (Check fine tempi) ---
        if ($current_phase == Configure::read('PHASE_TIME_PANEL')) {
            
            $athlete_count = $this->AthleteInscriptions->find()
                ->where(['categorycode_id' => $categorycode_id, 'deleted' => 0])
                ->count();
            
            // Conta quanti hanno un tempo totale
            $timed_count = $this->ResultsTimed->find()
                ->where([
                    'categorycode_id' => $categorycode_id,
                    'total_time IS NOT NULL'
                ])
                ->count();

            // Se tutti hanno finito il percorso
            if ($athlete_count > 0 && $athlete_count == $timed_count) {
                
                // 1. Calcola Classifica Tempi (chi ha impiegato meno vince)
                $this->_calculateTimedRankings($categorycode_id);
                
                return $this->_updatePhase($stateRecord, Configure::read('TIMED_FINALIZING'));
            }
        }

        // Se non siamo in nessuno di questi casi speciali, restituisci la fase attuale
        return $current_phase;
    }

    /**
     * Verifica se la categoria prevede solo la classifica a punteggio
     * senza fasi a eliminazione diretta (es. Palloncino, Kata Bambini, Percorso).
     */
    private function _isDirectFinalCategory($categorycode_id)
    {        
        $directFinalCodes = ['PAL', 'KAG'];
        
        foreach ($directFinalCodes as $code) {
            if (strpos($categorycode_id, $code) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Calcola il ranking per il round di qualificazione gestendo gli SPAREGGI TECNICI.
     * * Regole di ordinamento:
     * 1. Totale Punteggio (Somma dei 3 validi).
     * 2. Punteggio Minimo tra i 3 validi (Chi ha il "peggiore" più alto vince).
     * 3. Punteggio Massimo tra i 3 validi (Chi ha il "migliore" più alto vince).
     * 4. Se ancora pari -> Ex Aequo (ranking uguale -> spareggio fisico).
     */
    private function _calculateAndSaveRankings($categorycode_id)
    {
        $this->loadModel('ResultsJudgedPanel');

        // 1. Recupera tutti i risultati (senza ordinamento SQL, lo facciamo in PHP)
        $results = $this->ResultsJudgedPanel->find()
            ->where([
                'categorycode_id' => $categorycode_id,
                'round' => Configure::read('ROUND_KATA_QUALIFICA'),
                'total_score IS NOT NULL'
            ])
            ->toArray();

        if (empty($results)) return;

        // 2. Ordinamento avanzato in PHP (usort)
        usort($results, function($a, $b) {
            // Recupera le statistiche per il confronto
            $statsA = $this->_getKataStats($a);
            $statsB = $this->_getKataStats($b);

            // CRITERIO 1: Totale (Decrescente)
            // Usiamo la spaceship operator (<=>). Per decrescente invertiamo $b e $a.
            // Moltiplichiamo per 1000 per evitare problemi con i float
            $diffTotal = ($statsB['total'] * 100) <=> ($statsA['total'] * 100);
            if ($diffTotal !== 0) return $diffTotal;

            // CRITERIO 2: Minimo dei Validi (Decrescente)
            // "Chi ha il minimo più alto passa in testa"
            $diffMin = ($statsB['min_valid'] * 100) <=> ($statsA['min_valid'] * 100);
            if ($diffMin !== 0) return $diffMin;

            // CRITERIO 3: Massimo dei Validi (Decrescente)
            // "Chi ha il massimo più alto passa"
            $diffMax = ($statsB['max_valid'] * 100) <=> ($statsA['max_valid'] * 100);
            if ($diffMax !== 0) return $diffMax;

            // CRITERIO 4: Perfetta parità
            return 0;
        });

        // 3. Assegnazione Ranking (Dense Rank)
        $rank = 0;
        // Teniamo traccia delle stats precedenti per capire se è un pareggio perfetto
        $lastStats = null; 
        
        $entitiesToSave = [];

        foreach ($results as $result) {
            $currentStats = $this->_getKataStats($result);

            // Se è il primo o se le stats sono diverse dal precedente -> incrementa rank
            // Nota: Se le stats sono identiche (totale, min e max uguali), il rank NON incrementa
            if ($lastStats === null || $this->_areStatsDifferent($lastStats, $currentStats)) {
                $rank++;
            }

            $result->pool_ranking = $rank;
            $result->setDirty('pool_ranking', true);
            $entitiesToSave[] = $result;

            $lastStats = $currentStats;
        }

        // 4. Salvataggio atomico
        $connection = $this->ResultsJudgedPanel->getConnection();
        try {
            $connection->transactional(function () use ($entitiesToSave) {
                if (!$this->ResultsJudgedPanel->saveMany($entitiesToSave)) {
                    throw new \Exception("Impossibile salvare i ranking.");
                }
            });
        } catch (\Exception $e) {
            Log::error("Errore ranking Kata: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper per estrarre i dati di confronto da un record
     */
    private function _getKataStats($entity)
    {
        // Mettiamo i 5 voti in un array
        $scores = [
            (float)$entity->referee_1, (float)$entity->referee_2,
            (float)$entity->referee_3, (float)$entity->referee_4,
            (float)$entity->referee_5
        ];
        
        // Ordiniamo: indice 0 = min assoluto, indice 4 = max assoluto
        sort($scores);

        // I voti VALIDI sono quelli centrali: indici 1, 2, 3
        $validScores = [$scores[1], $scores[2], $scores[3]];

        // Riordiniamo i validi (anche se sono già ordinati, per sicurezza)
        sort($validScores);

        return [
            'total' => (float)$entity->total_score,
            'min_valid' => $validScores[0], // Il più basso dei validi
            'max_valid' => $validScores[2]  // Il più alto dei validi
        ];
    }

    /**
     * Confronta due set di statistiche per vedere se sono diversi
     */
    private function _areStatsDifferent($statsA, $statsB)
    {
        return (
            $statsA['total'] != $statsB['total'] ||
            $statsA['min_valid'] != $statsB['min_valid'] ||
            $statsA['max_valid'] != $statsB['max_valid']
        );
    }

    /**
     * Genera gli incontri per un tabellone a eliminazione (4, 8, 16, 32...)
     * Usa logica dinamica (pow 2) invece di if/else fissi.
     */
    private function _generateEliminationBracket($categorycode_id, array $atleti_ids)
    {
        $count = count($atleti_ids);
        // Trova la potenza di 2 più vicina (es. 6 atleti -> 8)
        $bracketSize = pow(2, ceil(log($count, 2)));
        
        // Riempi i buchi con NULL (BYE)
        $atletiPaddati = array_pad($atleti_ids, intval($bracketSize), null);

        $half = $bracketSize / 2;
        $matches = [];

        // --- FASE 0: Definisci l'ordine degli indici (Logica Split) ---
        // Vogliamo prima i dispari della prima metà (0, 2, 4...), poi i pari (1, 3, 5...)
        $indicesOrder = [];
        
        // Prima passata: 0, 2, 4... (Rank 1, 3, 5...)
        for ($i = 0; $i < $half; $i += 2) {
            $indicesOrder[] = $i;
        }
        
        // Seconda passata: 1, 3, 5... (Rank 2, 4, 6...)
        for ($i = 1; $i < $half; $i += 2) {
            $indicesOrder[] = $i;
        }

        // --- FASE 1: Genera le entity del PRIMO TURNO ---
        
        // Determina il round di partenza
        // 32->0, 16->1 (Ottavi), 8->2 (Quarti), 4->3 (Semi)
        $roundMap = [32 => 0, 16 => 1, 8 => 2, 4 => 3, 2 => 4]; 
        $currentRound = $roundMap[$bracketSize] ?? 1;

        $matchCounter = 1;

        foreach ($indicesOrder as $i) {
            $akaId = $atletiPaddati[$i];
            $aoId  = $atletiPaddati[$i + $half];

            $matches[] = $this->ResultsMatch->newEntity([
                'categorycode_id' => $categorycode_id,
                'round' => $currentRound,
                'match_number' => $matchCounter++,
                'athlete_aka_inscription_id' => $akaId,
                'athlete_ao_inscription_id' => $aoId,
                // Se AO è null, AKA vince subito (BYE)
                'winner_inscription_id' => ($aoId === null) ? $akaId : null,
                'method_of_win' => ($aoId === null) ? 'BYE' : null
            ]);
        }

        // --- FASE 2: Genera i TURNI SUCCESSIVI (Vuoti per avanzamento) ---
        // Questo è fondamentale per far funzionare l'avanzamento automatico
        
        $matchesInRound = count($matches); // Es. 2 Semifinali

        while ($matchesInRound > 1) {
            $matchesInRound = $matchesInRound / 2; // Dimezza (es. 2 -> 1 Finale)
            $currentRound++; // Avanza round (es. da 3 a 4)

            for ($k = 1; $k <= $matchesInRound; $k++) {
                $matches[] = $this->ResultsMatch->newEntity([
                    'categorycode_id' => $categorycode_id,
                    'round' => $currentRound,
                    'match_number' => $k,
                    'athlete_aka_inscription_id' => null, // Vuoto, attende vincitore
                    'athlete_ao_inscription_id' => null,  // Vuoto
                    'winner_inscription_id' => null
                ]);
            }
        }

        return $matches;
    }

    /**
     * Genera incontri Round Robin (1 vs 2, 1 vs 3, 2 vs 3)
     */
    private function _generateRoundRobinMatches($categorycode_id, array $ids)
    {
        //round 10 - girone all italiana
        $round = Configure::read('ROUND_ROBIN');
        $matches = [];
        // Match 1: A vs B
        $matches[] = $this->ResultsMatch->newEntity(['categorycode_id' => $categorycode_id, 'round' => $round, 'match_number' => 1, 'athlete_aka_inscription_id' => $ids[0], 'athlete_ao_inscription_id' => $ids[1]]);
        // Match 2: A vs C
        $matches[] = $this->ResultsMatch->newEntity(['categorycode_id' => $categorycode_id, 'round' => $round, 'match_number' => 2, 'athlete_aka_inscription_id' => $ids[0], 'athlete_ao_inscription_id' => $ids[2]]);
        // Match 3: B vs C
        $matches[] = $this->ResultsMatch->newEntity(['categorycode_id' => $categorycode_id, 'round' => $round, 'match_number' => 3, 'athlete_aka_inscription_id' => $ids[1], 'athlete_ao_inscription_id' => $ids[2]]);
        
        return $matches;
    }

    /**
     * Calcola la classifica del Girone all'Italiana (3 atleti).
     * Criteri: 1. Vittorie, 2. Punti Fatti, 3. Differenza Punti.
     */
    private function _finalizeRoundRobinRanking($categorycode_id)
    {
        $this->loadModel('ResultsMatch');
        $this->loadModel('AthleteInscriptions');

        // 1. Recupera i 3 incontri del girone
        $matches = $this->ResultsMatch->find()
            ->where([
                'categorycode_id' => $categorycode_id,
                'round' => Configure::read('ROUND_ROBIN'),
                'winner_inscription_id IS NOT NULL' // Assicuriamoci che siano finiti
            ])
            ->all();

        // Se non sono finiti tutti e 3 gli incontri, esci (o gestisci come preferisci)
        if ($matches->count() < 3) return;

        // 2. Inizializza statistiche
        $stats = [];
        
        // Helper per inizializzare un atleta nell'array se non esiste
        $initStat = function($id) use (&$stats) {
            if (!isset($stats[$id])) {
                $stats[$id] = [
                    'id' => $id,
                    'wins' => 0,
                    'points_scored' => 0,
                    'points_conceded' => 0,
                    'diff' => 0
                ];
            }
        };

        // 3. Calcola le statistiche ciclando sui match
        foreach ($matches as $match) {
            $akaId = $match->athlete_aka_inscription_id;
            $aoId = $match->athlete_ao_inscription_id;
            
            $initStat($akaId);
            $initStat($aoId);

            // Aggiorna Punti Fatti e Subiti
            $stats[$akaId]['points_scored'] += $match->score_aka;
            $stats[$akaId]['points_conceded'] += $match->score_ao;
            
            $stats[$aoId]['points_scored'] += $match->score_ao;
            $stats[$aoId]['points_conceded'] += $match->score_aka;

            // Aggiorna Vittorie
            if ($match->winner_inscription_id == $akaId) {
                $stats[$akaId]['wins']++;
            } elseif ($match->winner_inscription_id == $aoId) {
                $stats[$aoId]['wins']++;
            }
        }

        // Calcola differenza punti
        foreach ($stats as &$stat) {
            $stat['diff'] = $stat['points_scored'] - $stat['points_conceded'];
        }
        unset($stat); // rompe il riferimento

        // 4. ORDINAMENTO (Ranking)
        // Usort ordina l'array in base alla funzione di callback
        usort($stats, function ($a, $b) {
            // A. Criterio 1: Vittorie (Decrescente)
            if ($a['wins'] != $b['wins']) {
                return $b['wins'] <=> $a['wins'];
            }
            
            // B. Criterio 2: Punti Fatti (Decrescente)
            if ($a['points_scored'] != $b['points_scored']) {
                return $b['points_scored'] <=> $a['points_scored'];
            }

            // C. Criterio 3: Differenza Punti (Decrescente)
            return $b['diff'] <=> $a['diff'];
        });

        // 5. SALVA LA CLASSIFICA
        $rank = 1;
        $updates = [];
        
        foreach ($stats as $stat) {
            $updates[$stat['id']] = $rank;
            $rank++;
        }

        // Salvataggio su DB (Transazione)
        $this->AthleteInscriptions->getConnection()->transactional(function () use ($updates) {
            foreach ($updates as $athleteId => $ranking) {
                $entity = $this->AthleteInscriptions->get($athleteId);
                $entity->final_ranking = $ranking;
                $this->AthleteInscriptions->save($entity);
            }
        });
    }

    /**
     * Prende il vincitore di un match e lo scrive nel match del round successivo.
     */
    private function _advanceWinnerToNextRound($currentMatch)
    {

        $this->loadModel('ResultsMatch');
        $nextRound = $currentMatch->round + 1;
        
        // Calcola il numero del prossimo match.
        // Esempio tabellone a 8:
        // Quarti 1 e 2 -> vanno in Semi 1
        // Quarti 3 e 4 -> vanno in Semi 2
        // Formula: ceil(match_number / 2)
        $nextMatchNumber = (int)ceil($currentMatch->match_number / 2);

        // Trova il match di destinazione
        $nextMatch = $this->ResultsMatch->find()
            ->where([
                'categorycode_id' => $currentMatch->categorycode_id,
                'round' => $nextRound,
                'match_number' => $nextMatchNumber
            ])
            ->first();

        if ($nextMatch) {
            // Decidi se metterlo come AKA o AO
            // Se il match corrente era dispari (1, 3, 5) -> Va in AKA (slot sopra)
            // Se il match corrente era pari (2, 4, 6) -> Va in AO (slot sotto)
            if ($currentMatch->match_number % 2 != 0) {
                $nextMatch->athlete_aka_inscription_id = $currentMatch->winner_inscription_id;
            } else {
                $nextMatch->athlete_ao_inscription_id = $currentMatch->winner_inscription_id;
            }
            
            $this->ResultsMatch->save($nextMatch);
        }
    }

    private function _getRegisteredAthletes($categorycode_id)
    {
        $this->loadModel('AthleteInscriptions');
        return $this->AthleteInscriptions->find()
            ->where(['categorycode_id' => $categorycode_id, 'deleted' => 0])
            ->contain(['Athletes' => ['Clubs']]) // Per visualizzare nomi
            ->order(['Clubs.id', 'Athletes.id'])
            ->toArray();
    }

    /**
     * Calcola il punteggio KATA (min/max rimossi, somma 3 centrali).
     * @param array $scores Array di 5 punteggi float
     * @return array Dati calcolati
     */
    private function calculateKataScore(array $data): array
    {
        $referee_scores = [];

            switch ($data['category']) {
                case 'PAL':
                   $referee_scores = [
                        (float)$data['referee_1'],
                        (float)$data['referee_2'],
                        (float)$data['referee_3'],
                    ];
                    $filtered_scores = array_filter($referee_scores, 'is_numeric'); // Rimuovi eventuali NULL
                    sort($filtered_scores); // Ordina
                    $partial_score = array_sum($filtered_scores);
                    $penalty = $data['penalties_points']*0.2;

                    $total = $partial_score - $penalty; // Somma i 3 rimanenti
                    return [
                        'valid_1' => $filtered_scores[0], // Il primo dei 3 centrali
                        'valid_2' => $filtered_scores[1], // Il secondo
                        'valid_3' => $filtered_scores[2], // Il terzo
                        'partial_score' => $partial_score,
                        'total_score' => $total
                    ];
                case 'KAG':
                    $referee_scores = [
                        (float)$data['referee_1'],
                        (float)$data['referee_2'],
                        (float)$data['referee_3'],
                    ];
                    $filtered_scores = array_filter($referee_scores, 'is_numeric'); // Rimuovi eventuali NULL
                    sort($filtered_scores); // Ordina
                    $total = array_sum($filtered_scores); // Somma i 3 rimanenti
                    return [
                        'valid_1' => $filtered_scores[0], // Il primo dei 3 centrali
                        'valid_2' => $filtered_scores[1], // Il secondo
                        'valid_3' => $filtered_scores[2], // Il terzo
                        'total_score' => $total
                    ];
                
                default:
                    $referee_scores = [
                        (float)$data['referee_1'],
                        (float)$data['referee_2'],
                        (float)$data['referee_3'],
                        (float)$data['referee_4'],
                        (float)$data['referee_5']
                    ];

                    $filtered_scores = array_filter($referee_scores, 'is_numeric'); // Rimuovi eventuali NULL
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
        
    }

    /**
     * Recupera lo storico dei Kata e punteggi per un atleta in una categoria.
     */
    public function getAthleteHistory()
    {

        $this->loadModel('ResultsMatch');
        $this->loadModel('ResultsJudgedPanel');
        $this->loadModel('Katas');

        $this->request->allowMethod(['post', 'get']);
        $athlete_inscription_id = $this->request->getData('athlete_inscription_id');
        $categorycode_id = $this->request->getData('categorycode_id');

        $history = [];

        // 1. RECUPERA DATI DAL PANEL (Qualifiche / Round 1)
        
        $panelData = $this->ResultsJudgedPanel->find()
            ->contain(['Katas']) // Assumi di avere la relazione con la tabella Kata
            ->where([
                'athlete_inscription_id' => $athlete_inscription_id,
                'categorycode_id' => $categorycode_id,
                'total_score IS NOT NULL'
            ])
            ->first();

        if ($panelData) {
            $history[] = [
                'round_name' => 'Qualifiche',
                'kata_name' => $panelData->kata ? $panelData->kata->kata_name : 'Sconosciuto', // O come si chiama la colonna
                'score' => $panelData->total_score
            ];
        }

        // 2. RECUPERA DATI DAI MATCH (Eliminatorie / Round 2+)
        
        $matches = $this->ResultsMatch->find()
            ->where([
                'categorycode_id' => $categorycode_id,
                'OR' => [
                    ['athlete_aka_inscription_id' => $athlete_inscription_id],
                    ['athlete_ao_inscription_id' => $athlete_inscription_id]
                ],
                // Escludiamo match senza punteggio (non ancora avvenuti)
                'winner_inscription_id IS NOT NULL' 
            ])
            ->order(['round' => 'ASC'])
            ->all();

        foreach ($matches as $match) {
            // Determina se l'atleta era AKA o AO in questo match
            $isAka = ($match->athlete_aka_inscription_id == $athlete_inscription_id);
            
            $kataName = $isAka 
                ? $this->Katas->get($match->kata_id_aka)
                : $this->Katas->get($match->kata_id_ao);
            
            $score = $isAka ? $match->score_aka : $match->score_ao;

            // Mappa i round numerici in nomi (puoi usare un array map o helper)
            $roundName = "Round " . $match->round;
            if ($match->round == 1) $roundName = "Ottavi"; // Esempio
            if ($match->round == 2) $roundName = "Quarti"; 
            if ($match->round == 3) $roundName = "Semifinale"; 
            if ($match->round == 4) $roundName = "Finale"; 
            // ... logica personalizzata per i nomi dei round

            $history[] = [
                'round_name' => $roundName,
                'kata_name' => $kataName->kata_name,
                'score' => $score
            ];
        }

        $this->apiResponse['success'] = true;
        $this->apiResponse['data'] = $history;
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
    }
    
    /**
     * Finalizza una categoria con 1-3 atleti
     */
    public function finalizeScoreOnlyCategory()
    {
        $this->loadModel('AthleteInscriptions');
        $this->loadModel('ResultsJudgedPanel');
        $this->loadModel('TatamiAssignments');

        $data = $this->request->getData();
        $categorycode_id = $data['categorycode_id'];

        $athlete_count = $this->AthleteInscriptions->find()
                ->where(['categorycode_id' => $categorycode_id, 'deleted' => 0])
                ->count();

        $stateRecord = $this->TatamiAssignments->find()
            ->where(['categorycode_id' => $data['categorycode_id']])
            ->first();

        if ($this->_isDirectFinalCategory($categorycode_id) || $athlete_count <= 4) {
                
            // GARA FINITA: Calcola podio e chiudi

            $panel_entries = $this->ResultsJudgedPanel->find()
                ->where([
                    'categorycode_id' => $data['categorycode_id'], 
                    'round' => Configure::read('ROUND_KATA_QUALIFICA'), 
                    'total_score IS NOT NULL'
                ])
                ->toArray();

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


                $this->_updatePhase($stateRecord, Configure::read('PHASE_FINALIZED'));
                $this->apiResponse['success'] = true;
                $this->apiResponse['message'] = 'Classifica stilata';

            } catch (\Exception $e) {
                Log::error('Errore finalizzazione gara a punteggio: ' . $e->getMessage());
            }                    
                    
        } else {
            // Vai ai tabelloni (5+ atleti)
            $this->_updatePhase($stateRecord, Configure::read('PHASE_AWAITING_BRACKETS'));
            $this->apiResponse['success'] = true;
            $this->apiResponse['message'] = 'Classifica stilata';
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
     * Helper sicuro per aggiornare la fase e salvarla
     */
    private function _updatePhase($stateRecord, $new_phase)
    {
        $this->loadModel('TatamiAssignments');

        $stateRecord->current_phase = $new_phase;
        $this->TatamiAssignments->save($stateRecord);
        return $new_phase;
    }
    private function _getPanelData($categorycode_id, $ranking = false){


        $this->loadModel('ResultsJudgedPanel');
        $this->loadModel('Categorycodes');

        if($ranking){
            $category = $this->Categorycodes->get($categorycode_id);
            $limit = 4;

            if($category->grado == 'Marrone/Nera'){

                $scored_count = $this->ResultsJudgedPanel->find()
                ->where([
                    'categorycode_id' => $categorycode_id, 
                    'round' => Configure::read('ROUND_KATA_QUALIFICA'), 
                    'total_score IS NOT NULL'
                ])
                ->count();

                if($scored_count > 8){
                    $limit = 8;
                }

            }

        
            return $this->ResultsJudgedPanel->find('all', [
            'contain' => ['AthleteInscriptions' =>['Athletes' => ['Clubs']]] 
            ])
            ->where(['ResultsJudgedPanel.categorycode_id' => $categorycode_id])
            ->order(['pool_ranking' => 'ASC'])
            ->limit($limit)
            ->toArray();

        }

        return $this->ResultsJudgedPanel->find('all', [
            'contain' => ['AthleteInscriptions' =>['Athletes' => ['Clubs']]]
        ])
        ->where(['ResultsJudgedPanel.categorycode_id' => $categorycode_id])
        ->toArray();   

    }

    /**
     * Recupera i dati per la fase a tempo (Percorso).
     */
    private function _getTimedData($categorycode_id)
    {
        $this->loadModel('ResultsTimed');

        return $this->ResultsTimed->find()
            ->contain([
                'AthleteInscriptions' => ['Athletes' => ['Clubs']]
            ])
            ->where(['ResultsTimed.categorycode_id' => $categorycode_id])
            // Ordiniamo per ordine di esecuzione o alfabetico (opzionale)
            ->order(['AthleteInscriptions.athlete_id' => 'ASC']) 
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

            if($match->athlete_aka_inscription_id){
                $match->athlete_aka_inscription = $this->AthleteInscriptions->find()->where(['AthleteInscriptions.id' => $match->athlete_aka_inscription_id])->contain(['Athletes' => ['Clubs']])->first();
            }

            if($match->athlete_ao_inscription_id){                           
                $match->athlete_ao_inscription = $this->AthleteInscriptions->find()->where(['AthleteInscriptions.id' => $match->athlete_ao_inscription_id])->contain(['Athletes' => ['Clubs']])->first();
            }
            $grouped[$match->round][] = $match;
        }
        
        // Mappatura per i titoli (puoi renderla più elegante)
        $roundTitles = [
            1 => 'Ottavi di Finale',
            2 => 'Quarti di Finale',
            3 => 'Semifinali',
            4 => 'Finale',
            5 => 'Ripescaggio' // Adatta ai tuoi round
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

    public function getRankingData(){

        $request = $this->request->getData();
        $categorycode_id = $request['categorycode_id'];

        $data = $this->_getFinalRankingData($categorycode_id);

        $this->apiResponse['success'] = true;
        $this->apiResponse['message'] =  'Classifica recuperato.';
        $this->apiResponse['data'] = $data;


    }

    /**
     * Recupera la classifica finale (Podio).
     * Legge dalla colonna 'final_ranking' di AthleteInscriptions.
     */
    private function _getFinalRankingData($categorycode_id)
    {
        $this->loadModel('AthleteInscriptions');
        $this->loadModel('ResultsTimed');
        $this->loadModel('ResultsJudgedPanel');

        $rankings = $this->AthleteInscriptions->find()
            ->contain([
                // Assicurati che le associazioni siano corrette nel tuo Model
                'Athletes' => ['Clubs'] 
            ])
            ->where([
                'AthleteInscriptions.categorycode_id' => $categorycode_id,
                'AthleteInscriptions.final_ranking IS NOT NULL', // Prende solo chi ha un piazzamento
                'AthleteInscriptions.deleted' => 0
            ])
            ->order(['AthleteInscriptions.final_ranking' => 'ASC']) // 1°, 2°, 3°...
            ->toArray();
        
        if ($this->_isPercorso($categorycode_id)) {
            foreach ($rankings as $item) {
                $item->panel_data = $this->ResultsTimed->find()
                ->where(['athlete_inscription_id' => $item->id])
                ->first();
            }
        } else {
            foreach ($rankings as $item) {
                $item->panel_data = $this->ResultsJudgedPanel->find()
                ->where(['athlete_inscription_id' => $item->id])
                ->order(['round'])
                ->first();
            }
        }

        return $rankings;
    }

    /**
     * Calcola e salva la classifica finale basandosi sui risultati del tabellone.
     * Da chiamare quando la finale è conclusa.
     */
    private function _finalizeBracketRanking($categorycode_id)
    {
        $this->loadModel('ResultsMatch');
        $this->loadModel('AthleteInscriptions');


        // 1. Trova la Finale
        $finale = $this->ResultsMatch->find()
            ->where(['categorycode_id' => $categorycode_id, 'round' => 4]) // 4 = FINALE
            ->first();

        if (!$finale || !$finale->winner_inscription_id) return;

        $updates = [];

        // 1° Classificato (Vincitore Finale)
        $updates[$finale->winner_inscription_id] = 1;

        // 2° Classificato (Perdente Finale)
        // Nota: bisogna capire chi è il perdente
        $loserId = ($finale->winner_inscription_id == $finale->athlete_aka_inscription_id) 
            ? $finale->athlete_ao_inscription_id 
            : $finale->athlete_aka_inscription_id;
        
        if ($loserId) $updates[$loserId] = 2;

        // 3° Classificati (Perdenti Semifinali)
        // Nel Karate solitamente ci sono due terzi posti (senza finalina)
        $semifinali = $this->ResultsMatch->find()
            ->where(['categorycode_id' => $categorycode_id, 'round' => 3]); // 3 = SEMI

        foreach ($semifinali as $semi) {
            if ($semi->winner_inscription_id) {
                $semiLoser = ($semi->winner_inscription_id == $semi->athlete_aka_inscription_id)
                    ? $semi->athlete_ao_inscription_id
                    : $semi->athlete_aka_inscription_id;
                
                if ($semiLoser) $updates[$semiLoser] = 3;
            }
        }

        // Salva tutto su AthleteInscriptions
        foreach ($updates as $athleteId => $rank) {
            $entity = $this->AthleteInscriptions->get($athleteId);
            $entity->final_ranking = $rank;
            $this->AthleteInscriptions->save($entity);
        }
    }
}
