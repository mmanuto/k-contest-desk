<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;

/**
 * Clubs Controller
 *
 * @property \App\Model\Table\ClubsTable $Clubs
 * @method \App\Model\Entity\Club[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ClubsController extends ApiController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $clubs = $this->paginate($this->Clubs);

        $this->set(compact('clubs'));
    }

    /**
     * View method
     *
     * @param string|null $id Club id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $club = $this->Clubs->get($id, [
            'contain' => ['Athletes', 'ClubInscriptions', 'Teams'],
        ]);

        $this->set(compact('club'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $club = $this->Clubs->newEmptyEntity();
        if ($this->request->is('post')) {
            $club = $this->Clubs->patchEntity($club, $this->request->getData());
            if ($this->Clubs->save($club)) {
                $this->Flash->success(__('The club has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The club could not be saved. Please, try again.'));
        }
        $this->set(compact('club'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Club id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $club = $this->Clubs->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $club = $this->Clubs->patchEntity($club, $this->request->getData());
            if ($this->Clubs->save($club)) {
                $this->Flash->success(__('The club has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The club could not be saved. Please, try again.'));
        }
        $this->set(compact('club'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Club id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $club = $this->Clubs->get($id);
        if ($this->Clubs->delete($club)) {
            $this->Flash->success(__('The club has been deleted.'));
        } else {
            $this->Flash->error(__('The club could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function getClubs(){

        $clubList = $this->Clubs->find()->toArray();
        $this->apiResponse['success'] = true;
        $this->apiResponse['data'] = $clubList;

    }

    public function getClassificaSocieta() {
        $this->autoRender = false;
        $this->response = $this->response->withType('application/json');

        // 1. Query: Carica Club -> Atleti -> Iscrizioni (solo attive)
        $clubs = $this->Clubs->find()
            ->contain([
                'Athletes.AthleteInscriptions' => function($q) {
                    return $q->where(['AthleteInscriptions.deleted' => 0]);
                }
            ])
            ->all();

        $rankingData = [];

        foreach ($clubs as $club) {
            $stats = [
                'club_name' => $club->club_name,
                'athletes_count' => 0, // Conta atleti unici
                'golds' => 0,
                'silvers' => 0,
                'bronzes' => 0,
                'points' => 0
            ];

            // Se il club non ha atleti, passiamo oltre
            if (!empty($club->athletes)) {
                
                // --- CICLO SUGLI ATLETI ---
                foreach ($club->athletes as $athlete) {
                    
                    // Verifica fondamentale: L'atleta conta SOLO se ha almeno un'iscrizione attiva
                    // (Se si è cancellato da tutto, non deve dare i 2 punti)
                    if (empty($athlete->athlete_inscriptions)) {
                        continue; 
                    }

                    // ==================================================
                    // 1. PUNTI PARTECIPAZIONE (Una tantum per atleta)
                    // ==================================================
                    $stats['athletes_count']++; // Incrementa il contatore atleti fisici
                    $stats['points'] += 2;      // Aggiunge 2 punti fissi per la presenza

                    // ==================================================
                    // 2. PUNTI MEDAGLIE (Per ogni specialità fatta)
                    // ==================================================
                    foreach ($athlete->athlete_inscriptions as $ins) {
                        
                        if (!empty($ins->final_ranking)) {
                            switch ($ins->final_ranking) {
                                case 1:
                                    $stats['golds']++;
                                    $stats['points'] += 10;
                                    break;
                                case 2:
                                    $stats['silvers']++;
                                    $stats['points'] += 8;
                                    break;
                                case 3:
                                    $stats['bronzes']++;
                                    $stats['points'] += 6;
                                    break;
                            }
                        }
                    }
                }
            }

            // Aggiungiamo alla classifica solo se il club ha mosso la classifica
            if ($stats['points'] > 0 || $stats['athletes_count'] > 0) {
                $rankingData[] = $stats;
            }
        }

        // 3. ORDINAMENTO (Decrescente per punti)
        usort($rankingData, function ($a, $b) {
            if ($a['points'] == $b['points']) {
                return $b['golds'] <=> $a['golds']; // A parità di punti, vince chi ha più ori
            }
            return $b['points'] <=> $a['points'];
        });

        echo json_encode($rankingData);
    }
}
