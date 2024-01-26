<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersController extends ApiController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $users = $this->paginate($this->Users);

        $this->set(compact('users'));
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => ['Categorycodes', 'Scores', 'ScoresOld'],
        ]);

        $this->set(compact('user'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $categorycodes = $this->Users->Categorycodes->find('list', ['limit' => 200])->all();
        $this->set(compact('user', 'categorycodes'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => ['Categorycodes'],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $categorycodes = $this->Users->Categorycodes->find('list', ['limit' => 200])->all();
        $this->set(compact('user', 'categorycodes'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function login(){
        //$this->request->allowMethod('post');
    
        //$this->loadModel('Psychologists');
        //print_r($this->request->getData());
        $entity = $this->Users->newEntity($this->request->getData());
    
    

            $user = $this->Users->find()
                ->where([
                    'username' => $entity->username,
                    'password' => md5($entity->password)
                ])
                ->first();
    
            if (empty($user)) {
                $this->apiResponse['success'] = false;
                $this->apiResponse['error'] = 'Username o password errati';
    
                return;
            }
    
                //$this->apiResponse['token'] = JwtToken::generateToken($user);
                $this->apiResponse['data'] = $user;
                $this->apiResponse['success'] = true;
                $this->apiResponse['message'] = 'Logged in successfully.';
    
                unset($user);
            
        }


        public function updateUserData(){
            $data = $this->request->getData();
            /* print_r("dati: ");
            print_r($data); */
            if(isset($data['password'])){
                $data['password'] = md5($data['password']);
            }

            $user = $this->Users->get($data['id'], [
                'contain' => []
            ]);
                $user = $this->Users->patchEntity($user, $data);
                if ($this->Users->save($user)) {
                    $this->apiResponse['success'] = true;
                }else{
                    $this->apiResponse['success'] = false;
                }
                
        }

        public function getTatami(){
            $tatamiList = $this->Users->find()
            ->where([
                'username !=' => 'Admin'
            ])
            ->toArray();

            $this->apiResponse['data'] = $tatamiList;
	        $this->apiResponse['success'] = true;
        }
        

    public function addTatami()
    {

        for ($i=1; $i<9 ; $i++) { 
            $user = $this->Users->newEmptyEntity();
            $user->name = 'Tatami '.$i;
            $user->username = 'Tatami_'.$i;
            $user->password = md5('Tatami'.$i.'!');
            $this->Users->save($user);

            $this->set(compact('user'));
            $this->set('_serialize', ['user']);
        }
        

        
    }

    public function addAdmin()
    {


            $user = $this->Users->newEmptyEntity();
            $user->name = 'Admin';
            $user->username = 'Admin';
            $user->password = md5('CsenVeneto!');
            $this->Users->save($user);

            $this->set(compact('user'));
            $this->set('_serialize', ['user']);

        

        
    }

}
