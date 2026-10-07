<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;
use Cake\Datasource\ConnectionManager;


/**
 * Categorycodes Controller
 *
 * @property \App\Model\Table\CategorycodesTable $Categorycodes
 * @method \App\Model\Entity\Categorycode[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CategorycodesController extends ApiController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $categorycodes = $this->paginate($this->Categorycodes);

        $this->set(compact('categorycodes'));
    }

    /**
     * View method
     *
     * @param string|null $id Categorycode id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $categorycode = $this->Categorycodes->get($id, [
            'contain' => ['Users', 'AthleteInscriptions'],
        ]);

        $this->set(compact('categorycode'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $categorycode = $this->Categorycodes->newEmptyEntity();
        if ($this->request->is('post')) {
            $categorycode = $this->Categorycodes->patchEntity($categorycode, $this->request->getData());
            if ($this->Categorycodes->save($categorycode)) {
                $this->Flash->success(__('The categorycode has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The categorycode could not be saved. Please, try again.'));
        }
        $users = $this->Categorycodes->Users->find('list', ['limit' => 200])->all();
        $this->set(compact('categorycode', 'users'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Categorycode id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $categorycode = $this->Categorycodes->get($id, [
            'contain' => ['Users'],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $categorycode = $this->Categorycodes->patchEntity($categorycode, $this->request->getData());
            if ($this->Categorycodes->save($categorycode)) {
                $this->Flash->success(__('The categorycode has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The categorycode could not be saved. Please, try again.'));
        }
        $users = $this->Categorycodes->Users->find('list', ['limit' => 200])->all();
        $this->set(compact('categorycode', 'users'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Categorycode id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $categorycode = $this->Categorycodes->get($id);
        if ($this->Categorycodes->delete($categorycode)) {
            $this->Flash->success(__('The categorycode has been deleted.'));
        } else {
            $this->Flash->error(__('The categorycode could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Get all categories
     */

    public function getAllCategories(){
        $categoryList = $this->Categorycodes->find()->toArray();

        $this->apiResponse['data'] = $categoryList;
	    $this->apiResponse['success'] = true;

    }

    /**
     * Get all categories with numbers of registrated athletes (Stampa frontespizi)
     */

    public function getCategories(){
        $this->loadModel('AthleteInscriptions');
        $this->loadModel('TatamiAssignments');
        
        $conn = ConnectionManager::get('default');
        $stmt = $conn->execute("SELECT categorycodes.*, COUNT(athlete_inscriptions.id) AS n_athletes FROM athlete_inscriptions 
                                JOIN categorycodes ON categorycodes.id = athlete_inscriptions.categorycode_id 
                                WHERE categorycodes.\"codiceTipoCategorie\" = 3 AND athlete_inscriptions.deleted = 0
                                GROUP BY categorycodes.id ORDER BY categorycodes.order_number");

        $categories = $stmt->fetchAll('assoc');

        $newCategories = [];

        foreach ($categories as $category) {
           
            $status = $this->TatamiAssignments->find()
                ->where([
                    'categorycode_id' => $category['id']
                ])
                ->contain(['Users'])->first();
               
            if($status){
                $category['status'] = $status['status'];
                $category['tatami'] = $status['user']['name'];
            }else{
                $category['status'] = 'CAT_BACKLOG';
            }
            
            array_push($newCategories, $category);
        }

        $this->apiResponse['data'] = $newCategories;
	    $this->apiResponse['success'] = true;
    }

}
