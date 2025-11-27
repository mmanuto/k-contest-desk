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
                $data = $this->_getPanelData($categorycode_id);
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
     * AZIONE 3: Genera Tabelloni (Unificata KATA + KUMITE)
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
            $limit = ($total_athletes >= 8) ? 8 : 4;
            
            //TODO: da controllare. Controllo Pareggi (solo Kata)
            if ($this->_checkTies($top_scores, $limit, $categorycode_id, $stateRecord)) {
                return; // _checkTies gestisce la risposta e cambia stato
            }
            
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
            if (!$this->ResultsMatch->saveMany($nuoviIncontri)) {
                throw new \Exception('Salvataggio incontri fallito.');
            }
            $this->_updatePhase($stateRecord, Configure::read('PHASE_BRACKETS'));
            
            $this->apiResponse['success'] = true;
            $this->apiResponse['message'] = 'Tabellone generato con successo!';
        } catch (\Exception $e) {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] = $e->getMessage();
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

        // Se il frontend non manda il vincitore esplicito, calcolalo qui (opzionale)
        // if ($match->score_aka > $match->score_ao) ...

        if ($this->ResultsMatch->save($match)) {
            
            // --- LOGICA DI AVANZAMENTO ---
            
            // Se c'è un vincitore, dobbiamo gestire le conseguenze
            if ($match->winner_inscription_id) {
                
                // Recupera lo stato attuale per aggiornarlo se serve
                $stateRecord = $this->_findStateRecord($match->categorycode_id);

                // CASO A: ERA LA FINALE? -> FINALIZZA LA GARA
                // (Assumiamo che 4 sia la FINALE come da tue costanti, 
                //  oppure puoi calcolare qual è il round massimo per questa categoria)
                if ($match->round == Configure::read('ROUND_MATCH_FINALE')) {
                    
                    // 1. Scrivi i risultati nella tabella athlete_inscriptions (1°, 2°, 3°)
                    $this->_finalizeBracketRanking($match->categorycode_id);
                    
                    // 2. Chiudi la categoria
                    $this->_updatePhase($stateRecord, Configure::read('PHASE_FINALIZED'));
                    
                } 
                // CASO B: NON ERA LA FINALE -> SPOSTA IL VINCITORE AL TURNO DOPO
                else {
                    $this->_advanceWinnerToNextRound($match);
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

        $categorycode_id = $stateRecord->categorycode_id;
        $current_phase = $stateRecord->current_phase;

        // --- LOGICA 1: INIZIALIZZAZIONE (Bootstrap) ---
        if ($current_phase == Configure::read('PHASE_PENDING')) {
            
            // KUMITE: Non serve nulla, vai subito all'attesa
            if ($this->_isKumite($categorycode_id)) {
                return $this->_updatePhase($stateRecord, Configure::read('PHASE_AWAITING_BRACKETS'));
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

                // 2. Avanza
                if ($athlete_count <= 3) {
                    // Gara finita (1-3 atleti)
                    $this->_finalizeScoreOnlyCategory($categorycode_id); // Nota: passiamo ID o entities? Controlla la tua implementazione
                    return $this->_updatePhase($stateRecord, Configure::read('PHASE_FINALIZED'));
                } else {
                    // Vai ai tabelloni (4+ atleti)
                    return $this->_updatePhase($stateRecord, Configure::read('PHASE_AWAITING_BRACKETS'));
                }
            }
        }

        // Se non siamo in nessuno di questi casi speciali, restituisci la fase attuale
        return $current_phase;
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
    //TODO: da controllare
    private function _generateEliminationBracket($categorycode_id, array $atleti_ids)
    {
        $count = count($atleti_ids);
        // Trova la potenza di 2 più vicina (es. 6 atleti -> 8)
        $bracketSize = pow(2, ceil(log($count, 2)));
        
        // Riempi i buchi con NULL (BYE)
        $atletiPaddati = array_pad($atleti_ids, $bracketSize, null);
        
        // Genera accoppiamenti (1-8, 2-7...)
        // Implementazione semplificata: accoppia primo con ultimo, secondo con penultimo...
        // (Per seeding perfetto serve l'algoritmo "slither" o standard seeding)
        $pairings = [];
        for ($i = 0; $i < $bracketSize / 2; $i++) {
            $pairings[] = [
                'aka' => $atletiPaddati[$i],
                'ao' => $atletiPaddati[$bracketSize - 1 - $i]
            ];
        }

        // Determina il round iniziale
        $roundMap = [32 => 0, 16 => 1, 8 => 2, 4 => 3]; // 1=Ottavi, 2=Quarti...
        $startRound = $roundMap[$bracketSize] ?? 1;

        $matches = [];
        foreach ($pairings as $idx => $pair) {
            $matches[] = $this->ResultsMatch->newEntity([
                'categorycode_id' => $categorycode_id,
                'round' => $startRound,
                'match_number' => $idx + 1,
                'athlete_aka_inscription_id' => $pair['aka'],
                'athlete_ao_inscription_id' => $pair['ao'],
                // Gestione BYE automatico se AO è null
                'winner_inscription_id' => ($pair['ao'] === null) ? $pair['aka'] : null
            ]);
        }
        return $matches;
    }

    /**
     * Genera incontri Round Robin (1 vs 2, 1 vs 3, 2 vs 3)
     */
    //TODO: da controllare
    private function _generateRoundRobinMatches($categorycode_id, array $ids)
    {
        $matches = [];
        // Match 1: A vs B
        $matches[] = $this->ResultsMatch->newEntity(['categorycode_id' => $categorycode_id, 'round' => 1, 'match_number' => 1, 'athlete_aka_inscription_id' => $ids[0], 'athlete_ao_inscription_id' => $ids[1]]);
        // Match 2: A vs C
        $matches[] = $this->ResultsMatch->newEntity(['categorycode_id' => $categorycode_id, 'round' => 1, 'match_number' => 2, 'athlete_aka_inscription_id' => $ids[0], 'athlete_ao_inscription_id' => $ids[2]]);
        // Match 3: B vs C
        $matches[] = $this->ResultsMatch->newEntity(['categorycode_id' => $categorycode_id, 'round' => 1, 'match_number' => 3, 'athlete_aka_inscription_id' => $ids[1], 'athlete_ao_inscription_id' => $ids[2]]);
        
        return $matches;
    }

    /**
     * Prende il vincitore di un match e lo scrive nel match del round successivo.
     */
    private function _advanceWinnerToNextRound($currentMatch)
    {
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
                'competition_id' => $currentMatch->competition_id,
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
            ->toArray();
    }

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

    /**
     * Recupera la classifica finale (Podio).
     * Legge dalla colonna 'final_ranking' di AthleteInscriptions.
     */
    private function _getFinalRankingData($categorycode_id)
    {
        $this->loadModel('AthleteInscriptions');

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
