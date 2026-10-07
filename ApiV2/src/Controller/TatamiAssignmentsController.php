<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;
use Cake\Core\Configure;

/**
 * TatamiAssignments Controller
 *
 * @property \App\Model\Table\TatamiAssignmentsTable $TatamiAssignments
 * @method \App\Model\Entity\TatamiAssignment[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TatamiAssignmentsController extends ApiController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Users', 'Categorycodes'],
        ];
        $tatamiAssignments = $this->paginate($this->TatamiAssignments);

        $this->set(compact('tatamiAssignments'));
    }

    /**
     * View method
     *
     * @param string|null $id Tatami Assignment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $tatamiAssignment = $this->TatamiAssignments->get($id, [
            'contain' => ['Users', 'Categorycodes'],
        ]);

        $this->set(compact('tatamiAssignment'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $tatamiAssignment = $this->TatamiAssignments->newEmptyEntity();
        if ($this->request->is('post')) {
            $tatamiAssignment = $this->TatamiAssignments->patchEntity($tatamiAssignment, $this->request->getData());
            if ($this->TatamiAssignments->save($tatamiAssignment)) {
                $this->Flash->success(__('The tatami assignment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The tatami assignment could not be saved. Please, try again.'));
        }
        $users = $this->TatamiAssignments->Users->find('list', ['limit' => 200])->all();
        $categorycodes = $this->TatamiAssignments->Categorycodes->find('list', ['limit' => 200])->all();
        $this->set(compact('tatamiAssignment', 'users', 'categorycodes'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Tatami Assignment id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $tatamiAssignment = $this->TatamiAssignments->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $tatamiAssignment = $this->TatamiAssignments->patchEntity($tatamiAssignment, $this->request->getData());
            if ($this->TatamiAssignments->save($tatamiAssignment)) {
                $this->Flash->success(__('The tatami assignment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The tatami assignment could not be saved. Please, try again.'));
        }
        $users = $this->TatamiAssignments->Users->find('list', ['limit' => 200])->all();
        $categorycodes = $this->TatamiAssignments->Categorycodes->find('list', ['limit' => 200])->all();
        $this->set(compact('tatamiAssignment', 'users', 'categorycodes'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Tatami Assignment id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $tatamiAssignment = $this->TatamiAssignments->get($id);
        if ($this->TatamiAssignments->delete($tatamiAssignment)) {
            $this->Flash->success(__('The tatami assignment has been deleted.'));
        } else {
            $this->Flash->error(__('The tatami assignment could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /** =========================================================================================================
     * INIZIO FUNZIONI
     * ==========================================================================================================
     */

    public function getUserCategories(){
        $this->loadModel('Users');
        $request = $this->request->getData();
        if($request){
            $userCategories = $this->TatamiAssignments->find()
            ->where([
                'user_id' => $request['id'],
                'status <>' => 'CAT_CLOSED'
            ])
            ->contain(['Categorycodes'])
            ->toArray();

            $this->apiResponse['data'] = $userCategories;
            $this->apiResponse['success'] = true;
        }else{
            $users = $this->Users->find()
            ->where([
                'username !=' => 'Admin'
            ])
            ->toArray();

            foreach ($users as $user) {
                $user->userCategories = $this->TatamiAssignments->find()
                ->where([
                    'user_id' => $user->id
                ])
                ->contain(['Categorycodes'])
                ->toArray();
            }

            $this->apiResponse['data'] = $users;
            $this->apiResponse['success'] = true;
        }
    }

    public function updateCategoryStatus(){

        $this->loadModel('Competitions');
        $this->loadModel('SyncOutbox');

        $request = $this->request->getData();

        if (
            empty($request['id']) ||
            empty($request['categorycode_id']) ||
            empty($request['status']) ||
            empty($request['user_id'])
        ) {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] = 'Dati obbligatori mancanti.';

            return;
        }

         $competitions = $this->Competitions
                                ->find()
                                ->select(['id'])
                                ->limit(2)
                                ->toArray();
        
        if (count($competitions) !== 1) {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] =
                'Deve essere presente una sola competizione nel database locale.';

            return;
        }

        $competitionId = $competitions[0]->id;
        $connection = $this->TatamiAssignments->getConnection();

        try {
            $outboxId = $connection->transactional(
                function () use ($request, $competitionId) {
                    $assignment = $this->TatamiAssignments->get($request['id']);

                    $assignment = $this->TatamiAssignments->patchEntity(
                        $assignment,
                        [
                            'status' => $request['status'],
                        ]
                    );

                    $this->TatamiAssignments->saveOrFail($assignment);

                    $outbox = $this->SyncOutbox->newEntity([
                        'event_type' => 'CATEGORY_STATUS_UPDATED',
                        'endpoint' => 'users/updateCategoryStatus',
                        'payload' => [
                            'categorycode_id' => $request['categorycode_id'],
                            'status' => $request['status'],
                            'tatami' => $request['user_id'],
                            'competition_id' => $competitionId,
                        ],
                        'status' => 'PENDING',
                        'attempts' => 0,
                    ]);

                    $this->SyncOutbox->saveOrFail($outbox);

                    return $outbox->id;
                }
            );

            $this->apiResponse['success'] = true;
            $this->apiResponse['sync_queued'] = true;
            $this->apiResponse['sync_outbox_id'] = $outboxId;
        } catch (\Throwable $exception) {
            $this->log(
                'Errore aggiornamento categoria/outbox: ' .
                $exception->getMessage(),
                'error'
            );

            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] =
                'Impossibile aggiornare lo stato della categoria.';
        }
    }

    /**
     * Assegno categoria a tatami
     */
    public function saveUserCategories()
{
    $request = $this->request->getData();

    $this->loadModel('Competitions');
    $this->loadModel('SyncOutbox');

    if (
        empty($request['user_id']) ||
        empty($request['categorycode_id']) ||
        !isset($request['status'])
    ) {
        $this->apiResponse['success'] = false;
        $this->apiResponse['message'] = 'Dati obbligatori mancanti.';

        return;
    }

    $competitions = $this->Competitions
        ->find()
        ->select(['id'])
        ->limit(2)
        ->toArray();

    if (count($competitions) !== 1) {
        $this->apiResponse['success'] = false;
        $this->apiResponse['message'] =
            'Deve essere presente una sola competizione nel database locale.';

        return;
    }

    $competitionId = $competitions[0]->id;
    $connection = $this->TatamiAssignments->getConnection();

    try {
        $result = $connection->transactional(
            function () use ($request, $competitionId) {
                $assignment = $this->TatamiAssignments
                    ->find()
                    ->where([
                        'categorycode_id' => $request['categorycode_id'],
                    ])
                    ->first();

                if ($assignment) {
                    $assignment = $this->TatamiAssignments->patchEntity(
                        $assignment,
                        [
                            'user_id' => $request['user_id'],
                            'status' => $request['status'],
                        ]
                    );
                } else {
                    $assignment = $this->TatamiAssignments->newEntity([
                        'user_id' => $request['user_id'],
                        'categorycode_id' => $request['categorycode_id'],
                        'status' => $request['status'],
                    ]);
                }

                $this->TatamiAssignments->saveOrFail($assignment);

                $outbox = $this->SyncOutbox->newEntity([
                    'event_type' => 'TATAMI_ASSIGNMENT_CREATED',
                    'endpoint' => 'users/addTatamiAssignments',
                    'payload' => [
                        'tatami' => $request['user_id'],
                        'competition_id' => $competitionId,
                        'categorycode_id' => $request['categorycode_id'],
                        'status' => $request['status'],
                    ],
                    'status' => 'PENDING',
                    'attempts' => 0,
                ]);

                $this->SyncOutbox->saveOrFail($outbox);

                return [
                    'assignment_id' => $assignment->id,
                    'outbox_id' => $outbox->id,
                ];
            }
        );

        $this->apiResponse['success'] = true;
        $this->apiResponse['sync_queued'] = true;
        $this->apiResponse['assignment_id'] = $result['assignment_id'];
        $this->apiResponse['sync_outbox_id'] = $result['outbox_id'];
    } catch (\Throwable $exception) {
        $this->log(
            'Errore assegnazione categoria/outbox: ' .
            $exception->getMessage(),
            'error'
        );

        $this->apiResponse['success'] = false;
        $this->apiResponse['message'] =
            'Impossibile assegnare la categoria al tatami.';
    }
}

        
    /** =========================================================================================================
     * Controllo stato categorie
     * ==========================================================================================================
     */

    public function checkCategoryStatus(){

        Configure::load('constants');
        $categoryIds = $this->request->getData();

        foreach ($categoryIds as $catId) {
            $assignment = $this->TatamiAssignments->find()
                ->where(['categorycode_id' => $catId])
                ->first();

            // 1. Se non assegnata o stato TODO (0) -> SICURO
            if (!$assignment || $assignment->status == Configure::read('STATUS_TODO')) {
                continue; 
            }

            // 2. Se stato OPEN (1) -> AVVISO + RESET TOTALE
            if ($assignment->status == Configure::read('STATUS_OPEN')) {
                $this->apiResponse['success'] = true;
                $this->apiResponse['data'] = [
                    'safety' => false,
                    'status' => Configure::read('STATUS_OPEN'),
                    'message' => "La categoria $catId è già stata APERTA sul Tatami. Continuando, tutti i record di gara verranno resettati e la categoria tornerà in stato 'Da Iniziare'. Vuoi procedere?"
                ];
                return;
            }

            // 3. Se stato DOING (2) -> CONTROLLO SPECIALITÀ
            if ($assignment->status == Configure::read('STATUS_DOING')) {
                if ($this->_isKumite($catId)) {
                    $this->apiResponse['success'] = true;
                    $this->apiResponse['data'] = ['safety' => false];
                    $this->apiResponse['message'] = "ERRORE: La categoria Kumite $catId è già iniziata. Non è possibile modificare le iscrizioni durante il corso della gara.";
                    return;
                } else {
                    $this->apiResponse['success'] = true;
                    $this->apiResponse['data'] = [
                        'safety' => false,
                        'status' => Configure::read('STATUS_DOING'),
                        'message' => "La categoria $catId è già IN CORSO. Continuando, verrà aggiunto/rimosso solo il singolo atleta dai risultati. Vuoi procedere?"
                    ];
                    return;
                }
            }
        }
        
        $this->apiResponse['success'] = true;
        $this->apiResponse['data'] = ['safety' => true];
        
    } 

    private function _isKumite($categorycode_id)
    {
        return (strpos($categorycode_id, 'KU') !== false); 
    }

    public function getKataList(){
        $this->loadModel('Katas');
        $kata_list = $this->Katas->find('All')->toArray();
        $this->apiResponse['data'] = $kata_list;
        $this->apiResponse['success'] = true;
    }

    public function getTatamiStatus() {

        $this->loadModel('Users');
        $this->loadModel('ResultsMatch'); 
        $this->loadModel('ResultsJudgedPanel');
        $this->loadModel('ResultsTimed');
        $this->loadModel('AthleteInscriptions'); 
        // 1. Recupera la lista dei Tatami dalla tabella Users
        // Filtriamo per ID che inizia con 'T' (o usa is_admin = 0 se preferisci)
        // Dallo screenshot vedo che i tatami hanno ID 'T1', 'T2', ecc.
        $tatamiUsers = $this->Users->find()
            ->where(['id LIKE' => 'T%']) // Prende T1, T2, T3...
            ->order(['id' => 'ASC'])      // Ordina per ID
            ->all();
        
        $runningStatuses = ['CAT_DOING', 'IN_PROGRESS']; // Quelli attivi
        $queuedStatuses  = ['CAT_TODO', 'CAT_OPEN']; // Quelli in coda/assegnati

        // Uniamo gli stati per la query
        $allBusyStatuses = array_merge($runningStatuses, $queuedStatuses);

        $dashboardData = [];

        foreach ($tatamiUsers as $user) {
            $tatamiId = $user->id;     // es: "T1"
            $tatamiName = $user->name; // es: "Tatami 1" (preso dal DB)

            // 2. Cerca se questo tatami ha una categoria ATTIVA ora
            $tasks = $this->TatamiAssignments->find()
                ->contain(['Categorycodes']) 
                ->where([
                    'user_id' => $tatamiId,
                    'status IN' => $allBusyStatuses
                ])
                ->order(['TatamiAssignments.id' => 'ASC'])
                ->all();

                // Preparo i contenitori
                $activeCategories = [];
                $queuedCategories = [];

                foreach ($tasks as $task) {

                    $category = $task->categorycode;
                    $catId = $task->categorycode_id;
                    $specialty = $category->specialita ?? $this->_guessSpecialtyFromCode($catId);
                    
                    // --- CALCOLO STATISTICHE (Uguale a prima) ---
                    $totalItems = 0; $doneItems = 0; $label = "";
                    $type = strtoupper($specialty);
                    
                    if (strpos($type, 'KUMITE') !== false || strpos($catId, 'KUA') !== false || strpos($catId, 'KUG') !== false) {
                        $totalItems = $this->ResultsMatch->find()->where(['categorycode_id' => $catId])->count();
                        $doneItems = $this->ResultsMatch->find()->where(['categorycode_id' => $catId, 'winner_inscription_id IS NOT NULL'])->count();
                        $label = "Incontri";
                    } else if(strpos($type, 'PERCORSO') !== false || strpos($catId, 'PER') !== false){
                        $totalItems = $this->ResultsTimed->find()->where(['categorycode_id' => $catId])->count();
                        $doneItems = $this->ResultsTimed->find()->where(['categorycode_id' => $catId, 'total_time IS NOT NULL'])->count();
                        $label = "Prove";
                    } else{
                        // 1. Conta le prove a PUNTEGGIO (Fase eliminatoria / Pool)
                        $totalPanel = $this->ResultsJudgedPanel->find()
                            ->where(['categorycode_id' => $catId])
                            ->count();

                        $donePanel = $this->ResultsJudgedPanel->find()
                            ->where(['categorycode_id' => $catId, 'total_score IS NOT NULL'])
                            ->count();

                        // 2. Conta gli SCONTRI DIRETTI (Fase finale / Medal Matches)
                        // Utile per il Kata Adulti che finisce a scontri
                        $totalMatch = $this->ResultsMatch->find()
                            ->where(['categorycode_id' => $catId])
                            ->count();

                        // NOTA: Verifica se nel tuo DB usi 'winner_id' o 'winner_inscription_id'
                        $doneMatch = $this->ResultsMatch->find()
                            ->where([
                                'categorycode_id' => $catId, 
                                'winner_inscription_id IS NOT NULL' 
                            ])
                            ->count();

                        // 3. SOMMA TOTALE (Punteggi + Match)
                        $totalItems = $totalPanel + $totalMatch;
                        $doneItems = $donePanel + $doneMatch;

                        // 4. Etichetta Dinamica per capire cosa sta succedendo
                        if ($totalMatch > 0 && $totalPanel > 0) {
                            $label = "Prove + Match"; // Fase mista
                        } elseif ($totalMatch > 0) {
                            $label = "Incontri"; // Solo scontri (es. finali)
                        } else {
                            $label = "Prove"; // Solo punteggio
                        }
                    }
                    
                    $remaining = $totalItems - $doneItems;
                    $percent = ($totalItems > 0) ? round(($doneItems / $totalItems) * 100) : 0;
                    $isClosing = ($remaining <= 3 || $percent >= 90);

                    // Creo l'oggetto dati per questa singola categoria
                    $categoryData = [
                        'category_id' => $catId,
                        'category_name' => $category->categoria.' '.$category->sesso.' '.$category->grado.' '.$category->cat_peso,
                        'specialty' => $specialty,
                        'current_phase' => $task->current_phase,
                        'total' => $totalItems,
                        'done' => $doneItems,
                        'remaining' => $remaining,
                        'percent' => $percent,
                        'is_closing' => $isClosing,
                        'label' => $label
                    ];

                    // --- SMISTAMENTO (In Corso vs Coda) ---
                    if (in_array($task->status, $runningStatuses)) {
                        $activeCategories[] = $categoryData;
                    } else {
                        $queuedCategories[] = $categoryData;
                    }
                }

                // Determino lo stato globale del Tatami
                $globalStatus = 'LIBERO';
                if (count($activeCategories) > 0) $globalStatus = 'OCCUPATO';
                elseif (count($queuedCategories) > 0) $globalStatus = 'IN ATTESA';

                $dashboardData[] = [
                    'id' => $tatamiId,
                    'name' => $user->name,
                    'status' => $globalStatus,
                    'active_categories' => $activeCategories, // Array di oggetti
                    'queued_categories' => $queuedCategories  // Array di oggetti
                ];
            }
            return $this->response->withType('application/json')
                          ->withStringBody(json_encode($dashboardData));

            
    }

    // Funzione helper se non hai il campo specialty nel DB
    private function _guessSpecialtyFromCode($code) {
        if (strpos($code, 'PER') === 0) return 'PERCORSO';
        if (strpos($code, 'PAL') === 0) return 'PALLONCINO';
        if (strpos($code, 'KAG') === 0) return 'KATA BAMBINI'; // Kata Giovani?
        if (strpos($code, 'KIA') === 0) return 'KATA ADULTI'; // Kata Giovani?
        if (strpos($code, 'KUA') === 0) return 'KUMITE';
        if (strpos($code, 'KUG') === 0) return 'KUMITE';
    return 'KATA';
}   
}
