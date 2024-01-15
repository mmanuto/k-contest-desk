<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;

/**
 * ClubInscriptions Controller
 *
 * @property \App\Model\Table\ClubInscriptionsTable $ClubInscriptions
 * @method \App\Model\Entity\ClubInscription[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ClubInscriptionsController extends ApiController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Clubs', 'Competitions'],
        ];
        $clubInscriptions = $this->paginate($this->ClubInscriptions);

        $this->set(compact('clubInscriptions'));
    }

    /**
     * View method
     *
     * @param string|null $id Club Inscription id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $clubInscription = $this->ClubInscriptions->get($id, [
            'contain' => ['Clubs', 'Competitions'],
        ]);

        $this->set(compact('clubInscription'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $clubInscription = $this->ClubInscriptions->newEmptyEntity();
        if ($this->request->is('post')) {
            $clubInscription = $this->ClubInscriptions->patchEntity($clubInscription, $this->request->getData());
            if ($this->ClubInscriptions->save($clubInscription)) {
                $this->Flash->success(__('The club inscription has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The club inscription could not be saved. Please, try again.'));
        }
        $clubs = $this->ClubInscriptions->Clubs->find('list', ['limit' => 200])->all();
        $competitions = $this->ClubInscriptions->Competitions->find('list', ['limit' => 200])->all();
        $this->set(compact('clubInscription', 'clubs', 'competitions'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Club Inscription id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $clubInscription = $this->ClubInscriptions->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $clubInscription = $this->ClubInscriptions->patchEntity($clubInscription, $this->request->getData());
            if ($this->ClubInscriptions->save($clubInscription)) {
                $this->Flash->success(__('The club inscription has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The club inscription could not be saved. Please, try again.'));
        }
        $clubs = $this->ClubInscriptions->Clubs->find('list', ['limit' => 200])->all();
        $competitions = $this->ClubInscriptions->Competitions->find('list', ['limit' => 200])->all();
        $this->set(compact('clubInscription', 'clubs', 'competitions'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Club Inscription id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $clubInscription = $this->ClubInscriptions->get($id);
        if ($this->ClubInscriptions->delete($clubInscription)) {
            $this->Flash->success(__('The club inscription has been deleted.'));
        } else {
            $this->Flash->error(__('The club inscription could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
