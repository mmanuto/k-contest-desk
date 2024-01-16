<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;

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

        
        foreach ($athleteList as $athleteItem) {

            // Recupero righe punteggi
            $scores = $this->Scores->find()
            ->where([
                'athlete_inscription_id' => $athleteItem->id
            ])
            ->toArray();

            //Sono già presenti --> Le associo all'atleta
            if($scores){
                $category = substr($athleteItem->categorycode->codice, 0, 3);
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
                $score_record->category_code = $athleteItem->categorycode->codice;
                $score_record->n_prova = 1;

                $category = substr($athleteItem->categorycode->codice, 0, 3);
                if($category == 'KIA'){
                    $count++;

                    if($count % 2 == 0){
                        $score_record->color = 'AO';
                        
                    }else{
                        $score_record->color = 'AKA';
                    }
                }else{
                    $score_record->color = $category;
                    if($category == 'KUA' || $category == 'KUG'){
                        $score_record->total = 0;
                        $score_record->yuko = 0;
                        $score_record->wazaari = 0;
                        $score_record->ippon = 0;
                        $score_record->type = 'C';
                    }
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

        /*if(substr($athleteList[0]->categorycode->codice, 0, 3) == 'KUA'){
            $this->generateKumiteMatch(count($athlete_second_match) > 0 ? $athlete_second_match : $athleteList);
        }*/
        $this->apiResponse['data'] = count($athlete_second_match) > 0 ? $athlete_second_match : $athleteList;
	    $this->apiResponse['success'] = true;
    }



   /** ===================================================================================================================================
    * ELIMINA ISCRIZIONE
    *=====================================================================================================================================
    */
    public function deleteInscription()
    {
        $data = $this->request->getData();

        $athleteInscription = $this->AthleteInscriptions->get($data['id']);
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
        $athleteInscription['categorycode_id'] = $data['categorycode_id'];

        if ($this->AthleteInscriptions->save($athleteInscription)) {

            $this->apiResponse['success'] = true;
        } else {
            $this->apiResponse['success'] = false;
        }


    }


    public function generateKumiteMatch($athleteList){

        $matches = array();

        if(count($athleteList) == 2){

            $athleteList[0]->scores->opponentid = $athleteList[1]->scores->athlete_inscription_id;
            $athleteList[0]->scores->color = 'AKA';

            $athleteList[1]->scores->opponentid = $athleteList[0]->scores->athlete_inscription_id;
            $athleteList[1]->scores->color = 'AO';


            $teams = array($athleteList);
            $matches = array($teams);
        }else if (count($athleteList) == 3){
            $athleteList[0]->scores->opponentid = $athleteList[1]->scores->athlete_inscription_id;
            $athleteList[0]->scores->color = 'AKA';

            $athleteList[1]->scores->opponentid = $athleteList[0]->scores->athlete_inscription_id;
            $athleteList[1]->scores->color = 'AO';


            $team1 = array($athleteList[0], $athleteList[1]);

            $athleteList[1]->scores->opponentid = $athleteList[2]->scores->athlete_inscription_id;
            $athleteList[1]->scores->color = 'AKA';

            $athleteList[2]->scores->opponentid = $athleteList[1]->scores->athlete_inscription_id;
            $athleteList[2]->scores->color = 'AO';


            $team2 = array($athleteList[1], $athleteList[2]);

            $athleteList[2]->scores->opponentid = $athleteList[1]->scores->athlete_inscription_id;
            $athleteList[2]->scores->color = 'AKA';

            $athleteList[1]->scores->opponentid = $athleteList[2]->scores->athlete_inscription_id;
            $athleteList[1]->scores->color = 'AO';


            $team3 = array($athleteList[2], $athleteList[1]);

            $matches = array($team1, $team2, $team3);
        } else if (count($athleteList <= 4)){
            
            $team1 = array();
            $team2 = array();

            $progressive = 1;
            foreach ($athleteList as $athlete) {
                $athlete->progress = $progressive;                

                if($progressive == 1 || $progressive == 3){
                    array_push($team1, $athlete);
                }else{
                    array_push($team2, $athlete);
                }

                $progressive++;
            }
            
            array_push($matches, $team1, $team2);

        }else if(count($athleteList <= 8)){

            $progressive = 1;

            $team1 = array();
            $team2 = array();
            $team3 = array();
            $team4 = array();

            foreach ($athleteList as $athlete) {
                $athlete->progress = $progressive;

                if($progressive == 1 || $progressive == 5){
                    array_push($team1, $athlete);
                }else if($progressive == 3 || $progressive == 7){
                    array_push($team2, $athlete);
                }else if($progressive == 2 || $progressive == 6){
                    array_push($team3, $athlete);
                }else{
                    array_push($team4, $athlete);
                }

                $progressive++;
            }

            array_push($matches, $team1, $team2, $team3, $team4);


        }else if(count($athleteList <= 16)){

            $couple_8 = array(array(1,9), array(5,13), array(3,11), array(7,15), array(2,10), array(6,14), array(4,12), array(8,16));
            $progressive = 1;

            $team1 = array();
            $team2 = array();
            $team3 = array();
            $team4 = array();
            $team5 = array();
            $team6 = array();
            $team7 = array();
            $team8 = array();

            foreach ($athleteList as $athlete) {
                $athlete->progress = $progressive;

                if($progressive == 1 || $progressive == 9){
                    array_push($team1, $athlete);
                }else if($progressive == 5 || $progressive == 13){
                    array_push($team2, $athlete);
                }else if($progressive == 3 || $progressive == 11){
                    array_push($team3, $athlete);
                }else if($progressive == 7 || $progressive == 15){
                    array_push($team4, $athlete);
                }else if($progressive == 2 || $progressive == 10){
                    array_push($team5, $athlete);
                }else if($progressive == 6 || $progressive == 14){
                    array_push($team6, $athlete);
                }else if($progressive == 4 || $progressive == 12){
                    array_push($team7, $athlete);
                }else{
                    array_push($team8, $athlete);
                }

                $progressive++;
            }

            array_push($matches, $team1, $team2, $team3, $team4, $team5, $team6, $team7, $team8);

        }

        foreach ($matches as $match) {

            if(count($match == 2)){
                $match[0]->scores->opponentid = $match[1]->scores->athlete_inscription_id;
                $match[0]->scores->color = 'AKA';
    
                $match[1]->scores->opponentid = $match[0]->scores->athlete_inscription_id;
                $match[1]->scores->color = 'AO';
            }else{
                $match[0]->scores->win = 1;
                $match[0]->scores->color = 'AKA';
            }
            
        }

        return $matches;

    
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
