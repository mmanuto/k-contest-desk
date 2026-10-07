<?php
declare(strict_types=1);

namespace App\Controller;
use Cake\I18n\FrozenDate;
use RestApi\Controller\ApiController;
use Cake\Datasource\ConnectionManager;
use Cake\Database\Type;
use Cake\I18n\FrozenTime;

Type::build('date')->setLocaleFormat('yyyy-MM-dd');

/**
 * Competitions Controller
 *
 * @property \App\Model\Table\CompetitionsTable $Competitions
 * @method \App\Model\Entity\Competition[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CompetitionsController extends ApiController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $competitions = $this->paginate($this->Competitions);

        $this->set(compact('competitions'));
    }

    /**
     * View method
     *
     * @param string|null $id Competition id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $competition = $this->Competitions->get($id, [
            'contain' => ['AthleteInscriptions', 'ClubInscriptions', 'Teams'],
        ]);

        $this->set(compact('competition'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $competition = $this->Competitions->newEmptyEntity();
        if ($this->request->is('post')) {
            $competition = $this->Competitions->patchEntity($competition, $this->request->getData());
            if ($this->Competitions->save($competition)) {
                $this->Flash->success(__('The competition has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The competition could not be saved. Please, try again.'));
        }
        $this->set(compact('competition'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Competition id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $competition = $this->Competitions->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $competition = $this->Competitions->patchEntity($competition, $this->request->getData());
            if ($this->Competitions->save($competition)) {
                $this->Flash->success(__('The competition has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The competition could not be saved. Please, try again.'));
        }
        $this->set(compact('competition'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Competition id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $competition = $this->Competitions->get($id);
        if ($this->Competitions->delete($competition)) {
            $this->Flash->success(__('The competition has been deleted.'));
        } else {
            $this->Flash->error(__('The competition could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function getCompetitionData(){
        
        $competition = $this->Competitions->find()
            ->where([
                'comp_status' => 1
            ])
            ->first();

            $this->apiResponse['data'] = $competition;
	        $this->apiResponse['success'] = true;
            
    }

    public function importCompetitionData(){

        set_time_limit(300); 
        ini_set('memory_limit', '512M');

        $this->loadModel('Athletes');
        $this->loadModel('Categorycodes');
        $this->loadModel('AthleteInscriptions');
        $this->loadModel('Clubs');
        $this->loadModel('ClubInscriptions');

        $data = $this->request->getData();
        $conn = $this->Competitions->getConnection();

        $response = ['success' => false, 'message' => ''];

        try {
            // 1. Inizia la Transazione
            $conn->begin();

            // 2. Pulisci le tabelle. PostgreSQL rende TRUNCATE transazionale:
            // un eventuale rollback ripristina anche i dati rimossi.
            // Se qualcosa va storto prima, non avrai cancellato i dati vecchi invano.
            $tables = ['athlete_inscriptions', 'club_inscriptions', 'athletes', 'clubs', 'categorycodes', 'competitions', 'tatami_assignments'];
            $conn->execute(
                'TRUNCATE TABLE ' . implode(', ', $tables) . ' RESTART IDENTITY CASCADE'
            );

            // Opzioni per forzare il salvataggio degli ID manualmente
            $saveOptions = [
                'validate' => false,
                'accessibleFields' => ['id' => true],
                'atomic' => false, 
                'checkExisting' => false 
            ];

            // 4. Importazione Dati (Usando patchEntities e saveMany per velocità)
            
            // -------------------------------------------------------------------
            // HELPER 1: Pulisce i dati vuoti e RE-INDICIZZA l'array (Fondamentale!)
            // -------------------------------------------------------------------
            $cleanData = function($items) {
                if (empty($items)) return [];
                    $filtered = array_filter($items, function($item) {
                    return !empty($item['id']) && $item['id'] !== '';
                });
                // array_values è cruciale per far coincidere gli indici con le Entities
                return array_values($filtered);
            };

            // -------------------------------------------------------------------
            // HELPER 2: La magia per forzare l'ID originale e la INSERT
            // -------------------------------------------------------------------
            $forceIds = function($entities, $sourceData) {
                foreach ($entities as $index => $entity) {
                    $entity->setAccess('id', true); // Sblocca il campo
                    $entity->id = $sourceData[$index]['id']; // Inietta l'ID originale
                    $entity->isNew(true); // Forza CakePHP a fare una INSERT e non un UPDATE
                }
                return $entities;
            };

            // --- 1. COMPETITION ---
            if (!empty($data['competition']) && !empty($data['competition']['id'])) {
                $compEntity = $this->Competitions->newEmptyEntity();
                $compEntity = $this->Competitions->patchEntity($compEntity, $data['competition'], $saveOptions);

                // Forza ID per la gara singola
                $compEntity->setAccess('id', true);
                $compEntity->id = $data['competition']['id'];
                $compEntity->isNew(true);

                if (!$this->Competitions->save($compEntity, $saveOptions)) {
                    throw new \Exception("Errore salvataggio Competition: " . json_encode($compEntity->getErrors()));
                }
            }

            // --- 2. CLUBS ---
            $cleanClubs = $cleanData($data['clubs'] ?? []);
            if (!empty($cleanClubs)) {
                $entities = $this->Clubs->newEntities($cleanClubs, $saveOptions);
                $entities = $forceIds($entities, $cleanClubs); // Applica la forzatura ID
                // saveMany restituisce le entità salvate o false in caso di fallimento grave
                $this->Clubs->saveMany($entities, $saveOptions);
                
                // Verifica errori specifici nelle entità
                foreach ($entities as $entity) {
                    if ($entity->hasErrors()) {
                        throw new \Exception("Errore Club ID " . $entity->id . ": " . json_encode($entity->getErrors()));
                    }
                }
            }

            // --- 3. ATHLETES ---
            $cleanAthletes = $cleanData($data['athletes'] ?? []);
            if (!empty($cleanAthletes)) {
                $entities = $this->Athletes->newEntities($cleanAthletes, $saveOptions);
                $entities = $forceIds($entities, $cleanAthletes); // Applica la forzatura ID
                $this->Athletes->saveMany($entities, $saveOptions);
                
                foreach ($entities as $entity) {
                    if ($entity->hasErrors()) {
                        throw new \Exception("Errore Atleta ID " . $entity->id . ": " . json_encode($entity->getErrors()));
                    }
                }
            }

            // --- 4. CATEGORY CODES ---
            $cleanCats = $cleanData($data['categorycodes'] ?? []);
            if (!empty($cleanCats)) {
                $entities = $this->Categorycodes->newEntities($cleanCats, $saveOptions);
                $entities = $forceIds($entities, $cleanCats); // Applica la forzatura ID
                $this->Categorycodes->saveMany($entities, $saveOptions);
                foreach ($entities as $entity) {
                    if ($entity->hasErrors()) {
                        throw new \Exception("Errore Categoria ID " . $entity->id . ": " . json_encode($entity->getErrors()));
                    }
                }
            }

            // --- 5. CLUB INSCRIPTIONS ---
            $cleanClubInscr = $cleanData($data['clubInscirption'] ?? []);
            if (!empty($cleanClubInscr)) {
                $entities = $this->ClubInscriptions->newEntities($cleanClubInscr, $saveOptions);
                $entities = $forceIds($entities, $cleanClubInscr); // Applica la forzatura ID
                $this->ClubInscriptions->saveMany($entities, $saveOptions);
                foreach ($entities as $entity) {
                    if ($entity->hasErrors()) {
                        throw new \Exception("Errore Club Inscription ID " . $entity->id . ": " . json_encode($entity->getErrors()));
                    }
                }
            }

            // --- 6. ATHLETE INSCRIPTIONS ---
            $cleanAthInscr = $cleanData($data['atheleInscirption'] ?? []);
            if (!empty($cleanAthInscr)) {
                $entities = $this->AthleteInscriptions->newEntities($cleanAthInscr, $saveOptions);
                $entities = $forceIds($entities, $cleanAthInscr); // Applica la forzatura ID
                $this->AthleteInscriptions->saveMany($entities, $saveOptions);
                foreach ($entities as $entity) {
                    if ($entity->hasErrors()) {
                        throw new \Exception("Errore Athlete Inscription ID " . $entity->id . ": " . json_encode($entity->getErrors()));
                    }
                }
            }

            // Conferma la transazione
            $conn->commit();

            $response['success'] = true;
            $response['message'] = 'Importazione completata con successo.';

        } catch (\Exception $e) {
            // Se c'è un errore, annulla tutto e torna allo stato precedente
            $conn->rollback();
            // Logga l'errore per capire cosa non va
            \Cake\Log\Log::error("Errore Importazione: " . $e->getMessage());
            
            $response['success'] = false;
            $response['message'] = 'Errore durante l\'importazione: ' . $e->getMessage();
            $this->response = $this->response->withStatus(500);
        }

        $this->apiResponse = $response;

    }

    public function getTotaleIscrizioni()
    {
        $conn = ConnectionManager::get('default');
        $stmt2 = $conn->execute("SELECT COUNT(DISTINCT athlete_inscriptions.athlete_id) AS n_athletes FROM athlete_inscriptions");
        $stmt = $conn->execute("SELECT COUNT(DISTINCT athlete_inscriptions.categorycode_id) AS n_categories FROM athlete_inscriptions");

        $athletes = $stmt2->fetchAll('assoc');
        $categories = $stmt->fetchAll('assoc');



        $this->apiResponse['data'] = ['athletes' => $athletes[0]['n_athletes'], 'categories' => $categories[0]['n_categories']];
        $this->apiResponse['success'] = true;

    }
}
