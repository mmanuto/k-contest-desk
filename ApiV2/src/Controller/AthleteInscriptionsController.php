<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;
use Cake\Core\Configure;

/**
 * AthleteInscriptions Controller
 *
 * @property \App\Model\Table\AthleteInscriptionsTable $AthleteInscriptions
 * @method \App\Model\Entity\AthleteInscription[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AthleteInscriptionsController extends ApiController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Athletes', 'Categorycodes', 'Competitions'],
        ];
        $athleteInscriptions = $this->paginate($this->AthleteInscriptions);

        $this->set(compact('athleteInscriptions'));
    }

    /**
     * View method
     *
     * @param string|null $id Athlete Inscription id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $athleteInscription = $this->AthleteInscriptions->get($id, [
            'contain' => ['Athletes', 'Categorycodes', 'Competitions', 'Scores', 'ScoresOld'],
        ]);

        $this->set(compact('athleteInscription'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $athleteInscription = $this->AthleteInscriptions->newEmptyEntity();
        if ($this->request->is('post')) {
            $athleteInscription = $this->AthleteInscriptions->patchEntity($athleteInscription, $this->request->getData());
            if ($this->AthleteInscriptions->save($athleteInscription)) {
                $this->Flash->success(__('The athlete inscription has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The athlete inscription could not be saved. Please, try again.'));
        }
        $athletes = $this->AthleteInscriptions->Athletes->find('list', ['limit' => 200])->all();
        $categorycodes = $this->AthleteInscriptions->Categorycodes->find('list', ['limit' => 200])->all();
        $competitions = $this->AthleteInscriptions->Competitions->find('list', ['limit' => 200])->all();
        $this->set(compact('athleteInscription', 'athletes', 'categorycodes', 'competitions'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Athlete Inscription id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $athleteInscription = $this->AthleteInscriptions->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $athleteInscription = $this->AthleteInscriptions->patchEntity($athleteInscription, $this->request->getData());
            if ($this->AthleteInscriptions->save($athleteInscription)) {
                $this->Flash->success(__('The athlete inscription has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The athlete inscription could not be saved. Please, try again.'));
        }
        $athletes = $this->AthleteInscriptions->Athletes->find('list', ['limit' => 200])->all();
        $categorycodes = $this->AthleteInscriptions->Categorycodes->find('list', ['limit' => 200])->all();
        $competitions = $this->AthleteInscriptions->Competitions->find('list', ['limit' => 200])->all();
        $this->set(compact('athleteInscription', 'athletes', 'categorycodes', 'competitions'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Athlete Inscription id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $athleteInscription = $this->AthleteInscriptions->get($id);
        if ($this->AthleteInscriptions->delete($athleteInscription)) {
            $this->Flash->success(__('The athlete inscription has been deleted.'));
        } else {
            $this->Flash->error(__('The athlete inscription could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * 
     */

    public function getAthleteList(){
        $athleteList = $this->AthleteInscriptions->find()
        ->contain(['Athletes' => ['Clubs'], 'Categorycodes'])
        ->order(['Clubs.id', 'Athletes.id'])
        ->toArray();

        $this->apiResponse['data'] =  $athleteList;
	    $this->apiResponse['success'] = true;
    }


    public function getAthletesByCategory(){

        $this->loadModel('Scores');
        $request = $this->request->getData();
        $athleteList = $this->AthleteInscriptions->find()
        ->where([
            'categorycode_id' => $request['categorycode_id'],
            'deleted' => 0
        ])
        ->contain(['Athletes' => ['Clubs'], 'Categorycodes'])
        ->order(['Clubs.id', 'Athletes.id'])
        ->toArray();

        $count = 0;
        $athlete_second_match = [];

        if($request['readonly'] == true){
            $this->apiResponse['data'] = $athleteList;
	        $this->apiResponse['success'] = true;
        }else{
            foreach ($athleteList as $athleteItem) {

                // Recupero righe punteggi
                $scores = $this->Scores->find()
                ->where([
                    'athlete_inscription_id' => $athleteItem->id,
                    'deleted' => 0
                ])
                ->toArray();
    
                //Sono già presenti --> Le associo all'atleta
                if($scores){
                    $category = substr($athleteItem->categorycode->id, 0, 3);
                    if(count($scores) > 1){
                        if($category == 'KIA'){
                            $max_nprova = 0;
    
                            foreach ($scores as $score_item) {
                                if($score_item->n_prova > $max_nprova){
                                    $athleteItem->scores = $score_item;
                                    $max_nprova = $score_item->n_prova;
                                }
                            }
                            array_push($athlete_second_match, $athleteItem);
                        }else{
                            $first_match_close = true;
                            $athleteItem->old_scores = [];
                            foreach ($scores as $score_item) {
                                if($score_item->type == 'C'){
                                    $athleteItem->scores = $score_item;
                                }else{
                                    array_push($athleteItem->old_scores, $score_item);
                                }
                            }
                        }
                        
                    }else{
                        $athleteItem->scores = $scores[0];
                    }
                    
                }else{
    
                    //Non ci sono --> le creo e le associo all'atleta.
                    $score_record = $this->Scores->newEmptyEntity();
                    $score_record->athlete_inscription_id = $athleteItem->id;
                    $score_record->categorycode_id = $athleteItem->categorycode->id;
                    $score_record->n_prova = 1;
    
                    $category = substr($athleteItem->categorycode->id, 0, 3);
  
                    $score_record->color = $category;
                    if($category == 'KUA' || $category == 'KUG'){
                        $score_record->total = 0;
                        $score_record->yuko = 0;
                        $score_record->wazaari = 0;
                        $score_record->ippon = 0;
                        $score_record->type = 'C';
                    }
                    
                    $newScore = $this->Scores->save($score_record);
                    $athleteItem->scores = $newScore;
                    
                }
                
            }
    
            if(count($athlete_second_match) > 0){
                $max_nprova = 0;
    
                foreach ($athlete_second_match as $item) {
                    if($item->scores->n_prova > $max_nprova){
                        $max_nprova = $item->scores->n_prova;
                    }
                }
    
                for($i = 0; $i< count($athlete_second_match); $i++){
                    if($athlete_second_match[$i]->scores->n_prova != $max_nprova){
                        array_splice($athlete_second_match, $i, 1);
                        $i--;
                    }
                }
            }
    
            $this->apiResponse['data'] = count($athlete_second_match) > 0 ? $athlete_second_match : $athleteList;
            $this->apiResponse['success'] = true;
        }

        
        
    }

    public function getTotaleProve(){


        // Esegue il conteggio dei record
        $total = $this->AthleteInscriptions->find()
            ->where([
                'deleted' => 0 // Esclude le iscrizioni rimosse logicamente
            ])
            ->count();

        // Prepara la risposta per il frontend Angular
        $this->apiResponse['success'] = true;
        $this->apiResponse['message'] = 'Totale prove recuperato con successo.';
        $this->apiResponse['data'] = $total;    
    }



   /** ===================================================================================================================================
    * ELIMINA ISCRIZIONE
    *=====================================================================================================================================
    */

    //TODO: controllare se va gestitita la divisione maschile/Femminile
    public function deleteInscription()
    {
        Configure::load('constants');
        $this->loadModel('TatamiAssignments');
        $this->loadModel('ResultsJudgedPanel');
        $this->loadModel('ResultsTimed');
        $request = $this->request->getData();

        

        $categoryStatus = $this->TatamiAssignments->find()
            ->where([
                'categorycode_id' => $request['categorycode_id']
            ])->first();

        switch ($categoryStatus->current_phase) {
            case Configure::read('PHASE_JUDGING_PANEL'):
                $recordToDelete = $this->ResultsJudgedPanel->find()
                ->where(['athlete_inscription_id' => $request['id']])
                ->first();
                $this->ResultsJudgedPanel->delete($recordToDelete);
                break;
            
            case Configure::read('PHASE_TIME_PANEL'):
                $recordToDelete = $this->ResultsTimed->find()
                ->where(['athlete_inscription_id' => $request['id']])
                ->first();
                $this->ResultsJudgedPanel->delete($recordToDelete);
                break;
            default:
                
                break;
        }


        $athleteInscription = $this->AthleteInscriptions->get($request['id']);
        $athleteInscription->deleted = 1;
        if ($this->AthleteInscriptions->save($athleteInscription)) {
    
            $this->apiResponse['success'] = true;
        } else {
            $this->apiResponse['success'] = false;
        }
        
    }

    /**
     * UPDATE CATEGORY
     */

    public function updateCategory(){
        $data = $this->request->getData();
        $athleteInscription = $this->AthleteInscriptions->get($data['id']);
        $athleteInscription['old_category'] = $athleteInscription['categorycode_id'];
        $athleteInscription['categorycode_id'] = $data['categorycode_id'];
        $athleteInscription['accorpamento'] = $data['accorpamento'] ? 1 : 0;
        $athleteInscription['modificato'] = 1;

        if ($this->AthleteInscriptions->save($athleteInscription)) {

            $this->apiResponse['success'] = true;
        } else {
            $this->apiResponse['success'] = false;
        }
    }

    public function addInscription(){
        $data = $this->request->getData(); 
        $athleteInscription = $this->AthleteInscriptions->get($data['inscription_id']);

        $newInscription = $this->AthleteInscriptions->newEmptyEntity();
        $newInscription->athlete_id = $athleteInscription->athlete_id;
        $newInscription->competition_id = $athleteInscription->competition_id;
        $newInscription->categorycode_id = $data['categorycode_id'];

        if ($this->AthleteInscriptions->save($newInscription)) {

            $this->apiResponse['success'] = true;
        } else {
            $this->apiResponse['success'] = false;
        }

    }

    public function splitCategory(){
        
        $this->loadModel('Categorycodes');
        $data = $this->request->getData();


        $splittedCategories = $this->Categorycodes->find()
            ->where([
                'OR' => [['id' => $data['id'].'-M'], ['id' => $data['id'].'-F']]
            ])->toArray();

        
        if(sizeof($splittedCategories) >0){

            foreach ($splittedCategories as $categoryItem) {
                if($categoryItem->sesso == 'M'){
                    $categoryMale = $categoryItem;
                }else if($categoryItem->sesso == 'F'){
                    $categoryFemale = $categoryItem;
                }
            }

        }else{
            $categoryMale = $this->Categorycodes->newEmptyEntity();
            $categoryMale = $this->Categorycodes->patchEntity($categoryMale, $data);
    
            $categoryFemale = $this->Categorycodes->newEmptyEntity();
            $categoryFemale = $this->Categorycodes->patchEntity($categoryFemale, $data);
    
            $categoryMale->id = $data['id'].'-M';
            $categoryMale->sesso = 'M';
            $categoryMale = $this->Categorycodes->save($categoryMale);
            
            $categoryFemale->id = $data['id'].'-F';
            $categoryFemale->sesso = 'F';
            $categoryFemale = $this->Categorycodes->save($categoryFemale);
        }

        $athleteList = $this->AthleteInscriptions->find()
            ->where([
                'categorycode_id' => $data['id']
            ])
            ->contain(['Athletes'])->toArray();


        foreach ($athleteList as $athlete) {
            if($athlete->athlete->sesso == 'F'){
                $athlete->old_category = $athlete->categorycode_id;
                $athlete->categorycode_id = $categoryFemale->id;
            }else if($athlete->athlete->sesso == 'M'){
                $athlete->old_category = $athlete->categorycode_id;
                $athlete->categorycode_id = $categoryMale->id;             
            }

            print_r($athlete);
            $this->AthleteInscriptions->save($athlete);
        }
        

    }
    
    public function getModifiedInscriptions(){

        $list = $this->AthleteInscriptions->find()
        ->where([
            'modificato' => 1
        ])->toArray();

        $this->apiResponse['data'] = $list;
        $this->apiResponse['success'] = true;
    }

    public function getKumiteList(){

        $this->loadModel('Categorycodes');

        $categoryList = $this->Categorycodes->find()
        ->where([
            'specialita IN' => ['Kumite', 'Kumite U12']
            ])
        ->toArray();

        if($categoryList){
            $result = [];

            foreach ($categoryList as $item) {
                $athleteList = $this->AthleteInscriptions->find()
                ->where([
                    'categorycode_id' => $item['id'],
                    'deleted'=> 0
                ])
                ->contain(['Athletes' => ['Clubs']])
                ->order(['Clubs.id', 'Athletes.id'])
                ->toArray();
                if($athleteList){
                    $item['athleteList'] = $athleteList;
                    $result[] = $item;
                }
            }
            $this->apiResponse['data'] = $result;
	        $this->apiResponse['success'] = true;
        }else{
	        $this->apiResponse['success'] = false;
        }
    }

}
