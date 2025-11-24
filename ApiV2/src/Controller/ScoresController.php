<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;
use Cake\Utility\Hash;

/**
 * Scores Controller
 *
 * @property \App\Model\Table\ScoresTable $Scores
 * @method \App\Model\Entity\Score[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ScoresController extends ApiController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['AthleteInscriptions', 'Users', 'Katas'],
        ];
        $scores = $this->paginate($this->Scores);

        $this->set(compact('scores'));
    }

    /**
     * View method
     *
     * @param string|null $id Score id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $score = $this->Scores->get($id, [
            'contain' => ['AthleteInscriptions', 'Users', 'Katas'],
        ]);

        $this->set(compact('score'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $score = $this->Scores->newEmptyEntity();
        if ($this->request->is('post')) {
            $score = $this->Scores->patchEntity($score, $this->request->getData());
            if ($this->Scores->save($score)) {
                $this->Flash->success(__('The score has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The score could not be saved. Please, try again.'));
        }
        $athleteInscriptions = $this->Scores->AthleteInscriptions->find('list', ['limit' => 200])->all();
        $users = $this->Scores->Users->find('list', ['limit' => 200])->all();
        $katas = $this->Scores->Katas->find('list', ['limit' => 200])->all();
        $this->set(compact('score', 'athleteInscriptions', 'users', 'katas'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Score id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $score = $this->Scores->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $score = $this->Scores->patchEntity($score, $this->request->getData());
            if ($this->Scores->save($score)) {
                $this->Flash->success(__('The score has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The score could not be saved. Please, try again.'));
        }
        $athleteInscriptions = $this->Scores->AthleteInscriptions->find('list', ['limit' => 200])->all();
        $users = $this->Scores->Users->find('list', ['limit' => 200])->all();
        $katas = $this->Scores->Katas->find('list', ['limit' => 200])->all();
        $this->set(compact('score', 'athleteInscriptions', 'users', 'katas'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Score id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $score = $this->Scores->get($id);
        if ($this->Scores->delete($score)) {
            $this->Flash->success(__('The score has been deleted.'));
        } else {
            $this->Flash->error(__('The score could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function saveScoresAndGetTotals(){

        $data = $this->request->getData();

        $category = $data['category'];

        switch ($category) {
            case 'KIA':
            case 'KAS':
                $this->getKataTotals($data);
                break;
            case 'KAG':
                $this->getKataPallTotals($data, $category);
                break;
            case 'PAL':
                $this->getKataPallTotals($data, $category);
                break;
            case 'PER':
                $this->getPercorsoTotals($data);
                break;
            
            default:
                # code...
                break;
        }

    }

    public function getKataTotals($data){


        $referee_scores = [$data['referee_1'], $data['referee_2'], $data['referee_3'], $data['referee_4'], $data['referee_5']];

        sort($referee_scores);

        $data['min'] = $referee_scores[0];
        $data['valid_1'] = $referee_scores[1];
        $data['valid_2'] = $referee_scores[2];
        $data['valid_3'] = $referee_scores[3];
        $data['max'] = $referee_scores[4]; 
        
        $data['total'] = $data['valid_1'] + $data['valid_2'] + $data['valid_3'];    
            

        $score = $this->Scores->get($data['id']);
        $score = $this->Scores->patchEntity($score, $data);
        if ($this->Scores->save($score)) {
            $this->apiResponse['success'] = true;
        }
    }

    public function getKataPallTotals($data, $category){
        
        $data['total_without_penalties'] = $data['referee_1'] + $data['referee_2'] + $data['referee_3']; 
        
        $data['total'] = $data['total_without_penalties'];

        $referee_scores = [$data['referee_1'], $data['referee_2'], $data['referee_3']];
        sort($referee_scores);
        $data['valid_1'] = $referee_scores[0];
        $data['valid_2'] = $referee_scores[1];
        $data['valid_3'] = $referee_scores[2];
        
        if ($data['penalty'] == null){
            $data['penalty'] = 0;
        }
        if ($category == 'PAL'){
            $penalty = $data['penalty']*0.2;

            $data['total'] = $data['total_without_penalties'] - $penalty;
        }      

        $score = $this->Scores->get($data['id']);
        $score = $this->Scores->patchEntity($score, $data);
        if ($this->Scores->save($score)) {
            $this->apiResponse['success'] = true;
        }
    }

    public function getPercorsoTotals($data){

        $score = $this->Scores->get($data['id']);
        $score = $this->Scores->patchEntity($score, $data);

       
        $total_minutes = $data['minutes'] ? $data['minutes'] : 0;
        $penalty = $data['penalty'] ? $data['penalty'] : 0;
        $total_seconds = $data['seconds'] + $penalty;
        $total_milliseconds = $data['milliseconds'];

        if($total_seconds >= 60){
            $total_seconds = $total_seconds - 60;
            $total_minutes++;
        }
        
        if($score->penalty == null){
            $score->penalty = 0;
        }
        $score->total_time = $total_minutes.':'.$total_seconds.':'.$total_milliseconds;
        $score->total_time_seconds = $total_minutes*60 + $total_seconds + $total_milliseconds/60;

        if ($this->Scores->save($score)) {
            $this->apiResponse['success'] = true;
        }
    }


    //======================================================= ELABORAZIONE PUNTEGGI ================================================================


    public function elaboraPunteggi(){
        $data = $this->request->getData();

        $category = $data['category'];
        $n_prova = $data['nprova'];

        $classification = [];

        if(substr($category, 0, 3) == 'PER'){
            $classification = $this->Scores->find('All')
            ->where([
                'categorycode_id' => $category,
                'total_time_seconds >' => 0,
                'deleted' => 0
            ])
            ->order(['total_time_seconds' => 'ASC'])
            ->toArray();
        }else{
            $classification = $this->Scores->find('All')
            ->where([
                'categorycode_id' => $category,
                'n_prova' => $n_prova,
                'total >' => 0,
                'deleted' => 0
            ])
            ->order([
                'total' => 'DESC', 
                'valid_1' => 'DESC',
                'valid_3' => 'DESC'])
            ->toArray();

            foreach ($classification as $item) {
                $item->total = (string)$item->total;
            }
            

        }

        $this->manageElimintorie($category, $data['grado'], $classification);


    }

    public function manageElimintorie($category, $grado, $classification){
        if(substr($category, 0, 3) == 'PER' || substr($category, 0, 3) == 'KAG' || substr($category, 0, 3) == 'PAL' || substr($category, 0, 3) == 'KAS' || (substr($category, 0, 3) == 'KIA' && count($classification) <=3)){
            $position = 1;

            foreach ($classification as $item) {
                if(substr($category, 0, 3) == 'PER' && $item->total_time_seconds > 0 || substr($category, 0, 3) != 'PER')
                {
                    $item->cl_position = $position;
                    $position++;
                }else{
                    $item->cl_position = -1;
                }
                $item->type = 'CL';
                $this->Scores->save($item);
            }

            $this->apiResponse['success'] = true;
            return;
            
        }else{

            
            //se numero atleti minore di 4 stilo classifica
            if(count($classification) <= 4){

                $spareggio = false;
                $groupedByValue = Hash::combine($classification, '{n}.id', '{n}', '{n}.total');
                //print_r($groupedByValue); die();

                foreach ($groupedByValue as $item) {
                    if(count($item) > 1){
                        $spareggio = true;
                    }
                }

                //if(!$spareggio){
                    $count = 1;
                    foreach ($classification as $item) {
                        $item->cl_position = $count;
                        $item->type = 'CL';
                        $count++;
                        $this->Scores->save($item);
                    }
                //}else{
                    //TODO: gestire lo spareggio - invio flag + $groupedByValue
                //}                
                

            }else{
                //per le cinture colorate o nere fino a 8 atleti tiro fuori i primi 4
                if($grado != 'Marrone/Nera' || count($classification) <= 8){

                    $count = 1;
                    foreach ($classification as $item) {
                        $item->cl_position = $count;
                        $this->Scores->save($item);

                        if($count <= 4){
                            $item->type = $item->type == 'E' ? 'E_2' : 'E_3';
                            $this->saveAthlete($item);
                        }
                        $count++;
                    }

                }else{
                    //pre le centure nere oltre gli 8 atleti tiro fuori i prmi 8
                    
                    $count = 1;
                    foreach ($classification as $item) {
                        $item->cl_position = $count;
                        $this->Scores->save($item);

                        if($count <= 8){
                            $item->type = $item->type == 'E' ? 'E_2' : 'E_3';
                            $this->saveAthlete($item);
                        }
                        $count++;
                    }
                }
            }                    

        }
        $this->apiResponse['success'] = true;
    }

    public function saveAthlete($athlete){

        $newAthlete = $this->Scores->newEmptyEntity();
        $newAthlete['athlete_inscription_id'] = $athlete->athlete_inscription_id;
        $newAthlete['user_id'] = $athlete->user_id;
        $newAthlete['categorycode_id'] = $athlete->categorycode_id;
        $newAthlete['type'] = $athlete->type;
        $newAthlete['n_prova'] = $athlete->n_prova + 1;
        $newAthlete['color'] = $athlete->color;
        $newAthlete['cl_position'] =  $athlete->cl_position;

        if($newAthlete['opponentid'] == -1){
            $newAthlete['cl_position'] = 3;
        }

        $this->Scores->save($newAthlete);
        return;
    }

      /*Spareggi

    1- Si comparano i punteggi più bassi
    2- Si comparano i punteggi più alti
    3- CASUALE


    public function resolveSpareggiAdulti($spareggi){

        
        

        foreach ($spareggi as $key => $val) {
            
        }

        array_multisort($names, SORT_ASC, $arr);
    }

    /*
    1- Si comparano i punteggi più bassi
    2- Si comparano i punteggi più alti
    3- Vince il più giovane
    */
    /*public function resolveSpareggiBambini(){

        $this->loadModel('AthleteInscriptions');
        //1- Si comparano i punteggi più bassi
        if($spareggi[0]['valid_1'] != $spareggi[1]['valid_1']){
            $valid_1 = array();

            foreach ($spareggi as $key => $val) {
                $valid_1[$key] = $val['valid_1'];
            }
    
            array_multisort($valid_1, SORT_DESC, $spareggi);
        }else if($spareggi[0]['valid_3'] != $spareggi[1]['valid_3']){
            //2- Si comparano i punteggi più alti
            $valid_3 = array();

            foreach ($spareggi as $key => $val) {
                $valid_3[$key] = $val['valid_3'];
            }
    
            array_multisort($valid_3, SORT_DESC, $spareggi);

        }else{
            
            
            $athleteList = array();
            foreach ($spareggi as $score) {
                $athlete = $this->AthleteInscriptions->find()
                    ->where([
                        'id' => $score['athlete_inscription_id']
                    ])
                    ->contain(['Athletes'])
                    ->first();
                array_push($athleteList, $athlete);
            }
        }
        

    }*/

    public function getPreviousMatch(){
        $data = $this->request->getData();

        $matches = $this->Scores->find('All')
            ->where([
                'categorycode_id' => $data['categorycode_id'],
                'athlete_inscription_id' => $data['athlete_inscription_id'],
                'deleted' => 0
            ])
            ->contain(['Katas'])
            ->order(['n_prova' => 'ASC'])
            ->toArray();
        
        $this->apiResponse['success'] = true;
        $this->apiResponse['data'] = $matches;
    }


    /* ======================================================= KUMITE =================================================================================*/

    public function matchAthletes(){
        $data = $this->request->getData();

        foreach ($data as $score) {
            $scoreRecord = $this->Scores->get($score['id']);
            $scoreRecord = $this->Scores->patchEntity($scoreRecord, $score);
            $this->Scores->save($scoreRecord);
        }
        $this->apiResponse['success'] = true;
    }
    public function saveAthleteScores(){
        $data = $this->request->getData();

        $akaScore = $this->Scores->get($data['aka']['scores']['id']);
        $akaScore = $this->Scores->patchEntity($akaScore, $data['aka']['scores']);
        $akaScore->type = 'O';
        $akaScore->senshu = $data['aka']['scores']['senshu'] ? 1 : 0;
        $akaScore->chui_1 = $data['aka']['scores']['chui_1'] ? 1 : 0;
        $akaScore->chui_2 = $data['aka']['scores']['chui_2'] ? 1 : 0;
        $akaScore->chui_3 = $data['aka']['scores']['chui_3'] ? 1 : 0;
        $akaScore->hans_chui = $data['aka']['scores']['hans_chui'] ? 1 : 0;
        $akaScore->hansoku = $data['aka']['scores']['hansoku'] ? 1 : 0;
        $akaScore->shikkaku = $data['aka']['scores']['shikkaku'] ? 1 : 0;

        $this->Scores->save($akaScore);

        $newAkaMatch = $this->Scores->newEmptyEntity();
        $newAkaMatch['athlete_inscription_id'] = $akaScore->athlete_inscription_id;
        $newAkaMatch['user_id'] = $akaScore->user_id;
        $newAkaMatch['categorycode_id'] = $akaScore->categorycode_id;
        $newAkaMatch['type'] = 'C';
        $newAkaMatch['n_prova'] = $akaScore->n_prova + 1;
        $newAkaMatch->total = 0;
        $newAkaMatch->yuko = 0;
        $newAkaMatch->wazaari = 0;
        $newAkaMatch->ippon = 0;
        $newAkaMatch->opponentid = null;
        
        if($akaScore->win == 0){
            $newAkaMatch['win'] = 0;
        }

        $this->Scores->save($newAkaMatch);


        $aoScore = $this->Scores->get($data['ao']['scores']['id']);
        $aoScore = $this->Scores->patchEntity($aoScore, $data['ao']['scores']);
        $aoScore->type = 'O';
        $aoScore->senshu = $data['ao']['scores']['senshu'] ? 1 : 0;
        $aoScore->chui_1 = $data['ao']['scores']['chui_1'] ? 1 : 0;
        $aoScore->chui_2 = $data['ao']['scores']['chui_2'] ? 1 : 0;
        $aoScore->chui_3 = $data['ao']['scores']['chui_3'] ? 1 : 0;
        $aoScore->hans_chui = $data['ao']['scores']['hans_chui'] ? 1 : 0;
        $aoScore->hansoku = $data['ao']['scores']['hansoku'] ? 1 : 0;
        $aoScore->shikkaku = $data['ao']['scores']['shikkaku'] ? 1 : 0;
        $this->Scores->save($aoScore);

        $newAoMatch = $this->Scores->newEmptyEntity();
        $newAoMatch['athlete_inscription_id'] = $aoScore->athlete_inscription_id;
        $newAoMatch['user_id'] = $aoScore->user_id;
        $newAoMatch['categorycode_id'] = $aoScore->categorycode_id;
        $newAoMatch['type'] = 'C';
        $newAoMatch['n_prova'] = $aoScore->n_prova + 1;
        $newAoMatch['total'] = 0;
        $newAoMatch['yuko'] = 0;
        $newAoMatch['wazaari'] = 0;
        $newAoMatch['ippon'] = 0;
        $newAoMatch['opponentid'] = null;
        
        if($aoScore->win == 0){
            $newAoMatch['win'] = 0;
        }
        
        $this->Scores->save($newAoMatch);

        $this->apiResponse['success'] = true;

    }


    public function getClassifiche(){
        $data = $this->request->getData();

    }
}
