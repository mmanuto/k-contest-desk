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
        $this->Competitions->connection()->transactional(function ($conn) {
            $sqls = $this->Competitions->schema()->truncateSql($this->Competitions->connection());
            foreach ($sqls as $sql) {
                $this->Competitions->connection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- AthleteInscriptions ---------------------------------------------------------
        $this->AthleteInscriptions->connection()->transactional(function ($conn) {
            $sqls = $this->AthleteInscriptions->schema()->truncateSql($this->AthleteInscriptions->connection());
            foreach ($sqls as $sql) {
                $this->AthleteInscriptions->connection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- ClubInscriptions ---------------------------------------------------------
        $this->ClubInscriptions->connection()->transactional(function ($conn) {
            $sqls = $this->ClubInscriptions->schema()->truncateSql($this->ClubInscriptions->connection());
            foreach ($sqls as $sql) {
                $this->ClubInscriptions->connection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- Athletes ---------------------------------------------------------
        $this->Athletes->connection()->transactional(function ($conn) {
            $sqls = $this->Athletes->schema()->truncateSql($this->Athletes->connection());
            foreach ($sqls as $sql) {
                $this->Athletes->connection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- Clubs ---------------------------------------------------------
        $this->Clubs->connection()->transactional(function ($conn) {
            $sqls = $this->Clubs->schema()->truncateSql($this->Clubs->connection());
            foreach ($sqls as $sql) {
                $this->Clubs->connection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- Categorycodes ---------------------------------------------------------
        $this->Categorycodes->connection()->transactional(function ($conn) {
            $sqls = $this->Categorycodes->schema()->truncateSql($this->Categorycodes->connection());
            foreach ($sqls as $sql) {
                $this->Categorycodes->connection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- Scores ---------------------------------------------------------
        $this->Scores->connection()->transactional(function ($conn) {
            $sqls = $this->Scores->schema()->truncateSql($this->Scores->connection());
            foreach ($sqls as $sql) {
                $this->Scores->connection()->execute($sql)->execute();
            }
        });

        // ----------------------------------- UsersCategorycodes ---------------------------------------------------------
        $this->UsersCategorycodes->connection()->transactional(function ($conn) {
            $sqls = $this->UsersCategorycodes->schema()->truncateSql($this->UsersCategorycodes->connection());
            foreach ($sqls as $sql) {
                $this->UsersCategorycodes->connection()->execute($sql)->execute();
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


        $this->Competitions->save($competition);
        
        //----------------------------------- CLUBS ----------------------------------------------------

        foreach ($clubs as $entity) {
            $newEntity = $this->Clubs->newEmptyEntity($entity);
            $newEntity->id = $entity['id'];
            $this->Clubs->save($newEntity);
        }

        //----------------------------------- ATHETES ----------------------------------------------------

        foreach ($athletes as $entity) {
            $newEntity = $this->Athletes->newEmptyEntity($entity);
            $newEntity->id = $entity['id'];
            $this->Athletes->save($newEntity);
        }

        //----------------------------------- CATEGORYCODES ----------------------------------------------------

        foreach ($categoryCodes as $entity) {
            $newEntity = $this->Categorycodes->newEmptyEntity($entity);
            $newEntity->id = $entity['id'];
            $this->Categorycodes->save($newEntity);
        }

        //----------------------------------- CLUB INSCRIPTION ----------------------------------------------------

        foreach ($clubInscirption as $entity) {
            $newEntity = $this->ClubInscriptions->newEmptyEntity($entity);
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
            $inscription->flag_modificato = $entity['flag_modificato'];

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
