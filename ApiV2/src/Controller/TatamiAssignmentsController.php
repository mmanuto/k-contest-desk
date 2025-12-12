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
        $request = $this->request->getData();
        $usersCategorycode = $this->TatamiAssignments->get($request['id']);
        $usersCategorycode = $this->TatamiAssignments->patchEntity($usersCategorycode, $request);
        if ($this->TatamiAssignments->save($usersCategorycode)) {
            $this->apiResponse['success'] = true;
        }else{
            $this->apiResponse['message'] = 'The user category could not be saved. Please, try again.';
            $this->apiResponse['success'] = false;
        }
    }

    /**
     * Assegno categoria a tatami
     */
    public function saveUserCategories(){
        
        $itemToSave = $this->request->getData();

        //controllo che non sia già assegnata ad un altro tatami
        $categoryStatus = $this->TatamiAssignments->find()
         ->where([
            'categorycode_id' => $itemToSave['categorycode_id']
         ])->first();

         //se è già su un altro tatami la sposto mantenendo lo stato
        if ($categoryStatus){
            $categoryStatus->user_id =  $itemToSave['user_id'];
            if ($this->TatamiAssignments->save($categoryStatus)) {
                $this->apiResponse['success'] = true;
            }else{
                $this->apiResponse['message'] = 'The user category could not be saved. Please, try again.';
                $this->apiResponse['success'] = false;
            }
        }else{ //Altrimenti creo un nunovo record
            $userCategory = $this->TatamiAssignments->newEmptyEntity();
            $userCategory = $this->TatamiAssignments->patchEntity($userCategory, $this->request->getData());
            if ($this->TatamiAssignments->save($userCategory)) {
                $this->apiResponse['success'] = true;
            }else{
                $this->apiResponse['message'] = 'The user category could not be saved. Please, try again.';
                $this->apiResponse['success'] = false;
            }
        }
    }

        
    /** =========================================================================================================
     * Controllo stato categorie
     * ==========================================================================================================
     */

    public function checkCategoryStatus(){
        Configure::load('constants');
        $categoryList = $this->request->getData();
        $response = [
            'safety' => false,
            'message' => 'Una o più categorie coninvolte sono già in corso sul tatami a cui sono state assegnate. non è possibile proseguire con l\'operazione.'
        ];
        
        $categoryStatus = $this->TatamiAssignments->find()
            ->where([
                'categorycode_id IN' => $categoryList
            ])->toArray();
        
        if(sizeof($categoryStatus) == 0){
            $response['safety'] = true;
            $response['message'] = 'sei sicuro di voler procedere alla cancellazione?';
        }else{
            
            switch ($categoryStatus[0]['status']) {
                case Configure::read('STATUS_BACKLOG'):
                case Configure::read('STATUS_TODO'):
                    $response['message'] = "sei sicuro di voler procedere alla cancellazione?";
                    $response['safety'] = true;
                case Configure::read('STATUS_OPEN'):
                    $response['message'] = "La categoria è già stata aperta ma non iniziata sul tatami a cui è assegnata. Proseguendo il tabellone sarà cancellato e ricreato Sicuro di voler procedere? ";
                    $response['safety'] = true;
                    if(sizeof($categoryStatus) == 1){
                        break;
                    }else if($categoryStatus[1]['status'] == Configure::read('STATUS_BACKLOG') ||
                        $categoryStatus[1]['status'] == Configure::read('STATUS_TODO') ||
                        $categoryStatus[1]['status'] == Configure::read('STATUS_OPEN')){

                            if($categoryStatus[1]['status'] == Configure::read('STATUS_OPEN')){
                                $response['message'] = "La categoria di destinazione è già stata aperta ma non iniziata sul tatami a cui è assegnata. Proseguendo il tabellone sarà cancellato e ricreato Sicuro di voler procedere? ";
                            }
                            $response['safety'] = true;
                    }else{
                        $response = [
                            'safety' => false,
                            'messsage' => 'Una o più categorie coninvolte sono già in corso sul tatami a cui sono state assegnate. non è possibile proseguire con l\'operazione.'
                        ];
                    }
                default:
                    # code...
                    break;
            }
}
        
        
        $this->apiResponse['success'] = true;
        $this->apiResponse['data'] = $response;
        
    } 


    public function getKataList(){
        $this->loadModel('Katas');
        $kata_list = $this->Katas->find('All')->toArray();
        $this->apiResponse['data'] = $kata_list;
        $this->apiResponse['success'] = true;
    }
}
