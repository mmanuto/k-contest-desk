<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;
use Cake\Datasource\ConnectionManager;
use Cake\Database\Type;
use Cake\I18n\Date;

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
                'stato' => 1
            ])
            ->first();

            $this->apiResponse['data'] = $competition;
	        $this->apiResponse['success'] = true;
            
    }

    protected function cleanTables(){

        $this->loadModel('Athletes');
        $this->loadModel('Categorycodes');
        $this->loadModel('AthleteInscriptions');
        $this->loadModel('Clubs');
        $this->loadModel('ClubInscriptions');
        $this->loadModel('Scores');
        $this->loadModel('UsersCategorycodes');

        // ----------------------------------- COMPETITIONS ---------------------------------------------------------
        $this->Competitions->getConnection()->transactional(function ($conn) {
            $sqls = $this->Competitions->getSchema()->truncateSql($this->Competitions->getConnection());
            foreach ($sqls as $sql) {
                $this->Competitions->getConnection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- AthleteInscriptions ---------------------------------------------------------
        $this->AthleteInscriptions->getConnection()->transactional(function ($conn) {
            $sqls = $this->AthleteInscriptions->getSchema()->truncateSql($this->AthleteInscriptions->getConnection());
            foreach ($sqls as $sql) {
                $this->AthleteInscriptions->getConnection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- ClubInscriptions ---------------------------------------------------------
        $this->ClubInscriptions->getConnection()->transactional(function ($conn) {
            $sqls = $this->ClubInscriptions->getSchema()->truncateSql($this->ClubInscriptions->getConnection());
            foreach ($sqls as $sql) {
                $this->ClubInscriptions->getConnection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- Athletes ---------------------------------------------------------
        $this->Athletes->getConnection()->transactional(function ($conn) {
            $sqls = $this->Athletes->getSchema()->truncateSql($this->Athletes->getConnection());
            foreach ($sqls as $sql) {
                $this->Athletes->getConnection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- Clubs ---------------------------------------------------------
        $this->Clubs->getConnection()->transactional(function ($conn) {
            $sqls = $this->Clubs->getSchema()->truncateSql($this->Clubs->getConnection());
            foreach ($sqls as $sql) {
                $this->Clubs->getConnection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- Categorycodes ---------------------------------------------------------
        $this->Categorycodes->getConnection()->transactional(function ($conn) {
            $sqls = $this->Categorycodes->getSchema()->truncateSql($this->Categorycodes->getConnection());
            foreach ($sqls as $sql) {
                $this->Categorycodes->getConnection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- Scores ---------------------------------------------------------
        $this->Scores->getConnection()->transactional(function ($conn) {
            $sqls = $this->Scores->getSchema()->truncateSql($this->Scores->getConnection());
            foreach ($sqls as $sql) {
                $this->Scores->getConnection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- UsersCategorycodes ---------------------------------------------------------
        $this->UsersCategorycodes->getConnection()->transactional(function ($conn) {
            $sqls = $this->UsersCategorycodes->getSchema()->truncateSql($this->UsersCategorycodes->getConnection());
            foreach ($sqls as $sql) {
                $this->UsersCategorycodes->getConnection()->execute($sql)->execute();
            }
        });

        $this->apiResponse['success'] = true;

    }


    public function importCompetitionData(){
        $this->loadModel('Athletes');
        $this->loadModel('Categorycodes');
        $this->loadModel('AthleteInscriptions');
        $this->loadModel('Clubs');
        $this->loadModel('ClubInscriptions');


        $this->cleanTables();
        $data = $this->request->getData();

        $clubs = $data['clubs'];
        $athletes = $data['athletes'];
        $competition = $data['competition'];
        $categoryCodes = $data['categoryCodes'];
        $clubInscirption = $data['clubInscirption'];
        
        //----------------------------------- COMPETITION ----------------------------------------------------

        
        
        $competition = $this->Competitions->newEmptyEntity();
        $competition = $this->Competitions->patchEntity($competition, $data['competition']);

        $competition->id = $data['competition']['id'];
        $competition->data_gara = new Date($data['competition']['data_gara']);
        $competition->apertura_iscrizioni = new Date($data['competition']['apertura_iscrizioni']);
        $competition->chiusura_iscrizioni = new Date($data['competition']['chiusura_iscrizioni']);
        $competition->stato = 1;

        $this->Competitions->save($competition);
        
        //----------------------------------- CLUBS ----------------------------------------------------

        foreach ($clubs as $entity) {
            $newEntity = $this->Clubs->newEmptyEntity();
            $newEntity = $this->Clubs->patchEntity($newEntity, $entity);
            $newEntity->id = $entity['id'];
            $this->Clubs->save($newEntity);
        }

        //----------------------------------- ATHETES ----------------------------------------------------

        foreach ($athletes as $entity) {
            $newEntity = $this->Athletes->newEmptyEntity();
            $newEntity = $this->Athletes->patchEntity($newEntity, $entity);
            $newEntity->id = $entity['id'];
            $newEntity->deleted = 0;
            $this->Athletes->save($newEntity);
        }

        //----------------------------------- CATEGORYCODES ----------------------------------------------------

        foreach ($categoryCodes as $entity) {
            $newEntity = $this->Categorycodes->newEmptyEntity();
            $newEntity = $this->Categorycodes->patchEntity($newEntity, $entity);
            $newEntity->id = $entity['id'];
            $this->Categorycodes->save($newEntity);
        }

        //----------------------------------- CLUB INSCRIPTION ----------------------------------------------------

        foreach ($clubInscirption as $entity) {
            $newEntity = $this->ClubInscriptions->newEmptyEntity();
            $newEntity = $this->ClubInscriptions->patchEntity($newEntity, $entity);
            $newEntity->id = $entity['id'];
            $this->ClubInscriptions->save($newEntity);
        }

        //----------------------------------- ATHLETE INSCRIPTION ----------------------------------------------------

 
        foreach ($data['atheleInscirption'] as $entity) {

            $inscription = $this->AthleteInscriptions->newEmptyEntity();
            $inscription->id = $entity['id'];
            $inscription->athlete_id = $entity['athlete_id'];
            $inscription->categorycode_id = $entity['category_code_id'];
            $inscription->competition_id = $entity['competition_id'];
            $inscription->modificato = $entity['modificato'];

            $this->AthleteInscriptions->save($inscription);
        }

        $this->apiResponse['success'] = true;

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
