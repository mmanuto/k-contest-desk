<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;
use Cake\Core\Configure;

/**
 * UsersCategorycodes Controller
 *
 * @property \App\Model\Table\UsersCategorycodesTable $UsersCategorycodes
 * @method \App\Model\Entity\UsersCategorycode[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersCategorycodesController extends ApiController
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
        $usersCategorycodes = $this->paginate($this->UsersCategorycodes);

        $this->set(compact('usersCategorycodes'));
    }

    /**
     * View method
     *
     * @param string|null $id Users Categorycode id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $usersCategorycode = $this->UsersCategorycodes->get($id, [
            'contain' => ['Users', 'Categorycodes'],
        ]);

        $this->set(compact('usersCategorycode'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $usersCategorycode = $this->UsersCategorycodes->newEmptyEntity();
        if ($this->request->is('post')) {
            $usersCategorycode = $this->UsersCategorycodes->patchEntity($usersCategorycode, $this->request->getData());
            if ($this->UsersCategorycodes->save($usersCategorycode)) {
                $this->Flash->success(__('The users categorycode has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The users categorycode could not be saved. Please, try again.'));
        }
        $users = $this->UsersCategorycodes->Users->find('list', ['limit' => 200])->all();
        $categorycodes = $this->UsersCategorycodes->Categorycodes->find('list', ['limit' => 200])->all();
        $this->set(compact('usersCategorycode', 'users', 'categorycodes'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Users Categorycode id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $usersCategorycode = $this->UsersCategorycodes->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $usersCategorycode = $this->UsersCategorycodes->patchEntity($usersCategorycode, $this->request->getData());
            if ($this->UsersCategorycodes->save($usersCategorycode)) {
                $this->Flash->success(__('The users categorycode has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The users categorycode could not be saved. Please, try again.'));
        }
        $users = $this->UsersCategorycodes->Users->find('list', ['limit' => 200])->all();
        $categorycodes = $this->UsersCategorycodes->Categorycodes->find('list', ['limit' => 200])->all();
        $this->set(compact('usersCategorycode', 'users', 'categorycodes'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Users Categorycode id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $usersCategorycode = $this->UsersCategorycodes->get($id);
        if ($this->UsersCategorycodes->delete($usersCategorycode)) {
            $this->Flash->success(__('The users categorycode has been deleted.'));
        } else {
            $this->Flash->error(__('The users categorycode could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
