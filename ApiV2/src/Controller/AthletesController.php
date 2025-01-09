<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;

/**
 * Athletes Controller
 *
 * @property \App\Model\Table\AthletesTable $Athletes
 * @method \App\Model\Entity\Athlete[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AthletesController extends ApiController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Clubs', 'Federations'],
        ];
        $athletes = $this->paginate($this->Athletes);

        $this->set(compact('athletes'));
    }

    /**
     * View method
     *
     * @param string|null $id Athlete id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $athlete = $this->Athletes->get($id, [
            'contain' => ['Clubs', 'Federations', 'AthleteInscriptions'],
        ]);

        $this->set(compact('athlete'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $athlete = $this->Athletes->newEmptyEntity();
        if ($this->request->is('post')) {
            $athlete = $this->Athletes->patchEntity($athlete, $this->request->getData());
            if ($this->Athletes->save($athlete)) {
                $this->Flash->success(__('The athlete has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The athlete could not be saved. Please, try again.'));
        }
        $clubs = $this->Athletes->Clubs->find('list', ['limit' => 200])->all();
        $federations = $this->Athletes->Federations->find('list', ['limit' => 200])->all();
        $this->set(compact('athlete', 'clubs', 'federations'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Athlete id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $athlete = $this->Athletes->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $athlete = $this->Athletes->patchEntity($athlete, $this->request->getData());
            if ($this->Athletes->save($athlete)) {
                $this->Flash->success(__('The athlete has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The athlete could not be saved. Please, try again.'));
        }
        $clubs = $this->Athletes->Clubs->find('list', ['limit' => 200])->all();
        $federations = $this->Athletes->Federations->find('list', ['limit' => 200])->all();
        $this->set(compact('athlete', 'clubs', 'federations'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Athlete id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $athlete = $this->Athletes->get($id);
        if ($this->Athletes->delete($athlete)) {
            $this->Flash->success(__('The athlete has been deleted.'));
        } else {
            $this->Flash->error(__('The athlete could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    // -----------------------------------------------------------------------------------------------------
    // @ ELENCO FEDERAZIONI
    // -----------------------------------------------------------------------------------------------------

    public function getFederations()
    {
        $this->loadModel('Federations');
        $federationList = $this->Federations->find('all');

        if ($federationList) {
            $this->apiResponse['data'] = $federationList;
            $this->apiResponse['success'] = true;
        } else {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] = 'Errore nel recupero dei dati';
        }
    }

    // -----------------------------------------------------------------------------------------------------
    // @ ELENCO ATLETI APPARTENENTI ALLA SOCIETA'
    // -----------------------------------------------------------------------------------------------------

    //================= TUTTI GLI ATLETI DELLA SOCIETA' ====================================================
    public function getAthleteList()
    {

        $athleteList = $this->Athletes->find('all')->contain(['Clubs'])->toArray();
        if ($athleteList) {
            $this->apiResponse['success'] = true;
            $this->apiResponse['data'] = $athleteList;
        } else {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] = 'Non risultano atleti associati a qusta societa';
        }
    }


    // =================================== ATLETI ISCRITTI ALLA COMPETIZIONE =================================

    public function getIscritti()
    {
        $data = $this->request->getData();
        $this->loadModel('AthletesInscriptions');
        $this->loadModel('CategoryCodes');

        $conn = ConnectionManager::get('default');
        $stmt = $conn->execute("SELECT * FROM athlete_inscriptions 
                                JOIN athletes ON athletes.id = athlete_inscriptions.athlete_id 
                                JOIN categorycodes ON categorycodes.id = athlete_inscriptions.categorycode_id 
                                 WHERE athlete_inscriptions.competition_id =" . $data['competition_id'] . " AND athletes.club_id = " . $data['club_id']);

        $listaIscritti = $stmt->fetchAll('assoc');

        /* $listaIscritti = $this->AthletesInscriptions->find('all')
        ->select(['AthletesInscriptions.*, Athletes.*', 'CategoryCodes.*'])
                ->join([
                    'athletes' => [
                        'table' => 'athletes',
                        'type' => 'INNER',
                        'conditions' => 'athletes.id = AthletesInscriptions.athlete_id'
                    ],
                    'categorycodes' => [
                        'table' => 'categorycodes',
                        'type' => 'INNER',
                        'conditions' => 'categorycodes.id = AthletesInscriptions.categorycode_id'
                    ]

                ])
                ->where(['AthletesInscriptions.competition_id' => $data['competition_id'], 'athletes.club_id' => $data['club_id']])->toArray(); */

        if ($listaIscritti) {
            $this->apiResponse['success'] = true;
            $this->apiResponse['data'] = $listaIscritti;
        } else {
            $this->apiResponse['success'] = false;
            $this->apiResponse['message'] = 'Nessun iscritto';
        }
    }


    // -----------------------------------------------------------------------------------------------------
    // @ CATEGORIA E PROVE
    // -----------------------------------------------------------------------------------------------------

    // =========================================== CATEGORIA DATO L'ANNO DI NASCITA =================================================
    public function getCategoria()
    {
        
        $data = $this->request->getData();
        $conn = ConnectionManager::get('default');
        $stmt = $conn->execute("SELECT DISTINCT categoria FROM categorycodes WHERE codiceTipoCategorie =" . $data['categorycode_type'] . " AND " . $data['anno'] . " BETWEEN anno_min AND anno_max");
        
        
        $categoria = $stmt->fetchAll('assoc');


        if ($categoria) {
            $this->apiResponse['data'] = $categoria;
            $this->apiResponse['success'] = true;
        } else {
            $this->apiResponse['success'] = false;
        }
    }

    // =========================================== ELENCO PROVE PER ISCRIZIONE ===============================================
    public function getElencoProve()
    {
        $data = $this->request->getData();
        $conn = ConnectionManager::get('default');
        $stmt = $conn->execute("SELECT distinct specialita FROM categorycodes WHERE codiceTipoCategorie =" . $data['categorycode_type'] . " AND grado LIKE '%" . $data['grado'] . "%' AND " . $data['anno'] . " BETWEEN anno_min AND anno_max");

        $result = $stmt->fetchAll('assoc');
        $this->apiResponse['data'] = $result;
        $this->apiResponse['success'] = true;
    }


    // -----------------------------------------------------------------------------------------------------
    // @ SALVATAGGIO ATLETA/ISCRIZIONE
    // -----------------------------------------------------------------------------------------------------

    public function saveAtleta()
    {

        $this->loadModel('AthletesInscriptions');
        $data = $this->request->getData();
        $conn = ConnectionManager::get('default');

        $athltete = $this->request->getData()['athlete'];
        $prove = $this->request->getData()['prove'];

        //=============================== CONTROLLO E AGGIORNO ANAGRAFICA ATLETA ======================================
        if (isset($athltete['id'])) {

            $newAtlhete = $this->Athletes->get($athltete['id']);
        } else {
            $newAtlhete = $this->Athletes->find()
                ->where([
                    'cod_fiscale' => $athltete['cod_fiscale']
                ])->first();
            if (!$newAtlhete) {
                $newAtlhete = $this->Athletes->newEmptyEntity();
            }
        }
        $newAtlhete = $this->Athletes->patchEntity($newAtlhete, $athltete);
        $savedAtlhete = $this->Athletes->save($newAtlhete);
        

        if ($savedAtlhete) {

            $dataNascita = explode("/", $athltete['data_nascita']);

            $query = "SELECT * FROM categorycodes WHERE codiceTipoCategorie =" . $prove['categorycode_type'] . " AND grado LIKE '%" . $athltete['grado'] . "%' 
                        AND sesso LIKE '%" . $athltete['sesso'] . "%' AND " . $dataNascita[2] . " BETWEEN anno_min AND anno_max";
            if ($athltete['peso']) {
                $query .= "  AND " . $athltete['peso'] . " BETWEEN peso_min AND peso_max-1";
            } else {
                $athltete['peso'] = 0;
            }
            $stmt = $conn->execute($query);


            $result = $stmt->fetchAll('assoc');

            //===================================== ELIMINO EVENTUALI PROVE GIA' INSERITE ========================================
            $this->AthletesInscriptions
                ->deleteAll([
                    'athlete_id' => $savedAtlhete->id,
                    'competition_id' => $prove['competition_id']
                ]);

            //======================================= SALVO PROVE SELEZIONATE =============================================
            foreach ($prove['specialita'] as $prova) {
                foreach ($result as $record) {
                    if ($record['specialita'] == $prova) {
                        $inscription = $this->AthletesInscriptions->newEmptyEntity();
                        $inscription->athlete_id = $savedAtlhete->id;
                        $inscription->categorycode_id = $record['id'];
                        $inscription->competition_id = $prove['competition_id'];
                        $this->AthletesInscriptions->save($inscription);
                    }
                }
            }

            $this->apiResponse['success'] = true;
        } else {
            $this->apiResponse['success'] = false;
        }
    }

    // -----------------------------------------------------------------------------------------------------
    // @ ELIMINA ISCRIZIONE
    // -----------------------------------------------------------------------------------------------------
    public function deleteInscription()
    {

        $data = $this->request->getData();
        $this->loadModel('AthletesInscriptions');

        if ($this->AthletesInscriptions
            ->deleteAll([
                'athlete_id' => $data['athlete_id'],
                'competition_id' => $data['competition_id']
            ])
        ) {
            $this->apiResponse['success'] = true;
        } else {
            $this->apiResponse['success'] = false;
        }
    }

    // -----------------------------------------------------------------------------------------------------
    // @ CONFERMA ISCRIZIONE
    // -----------------------------------------------------------------------------------------------------

/*
    public function inviaDati()
    {

        $this->loadModel('Competitions');
        $this->loadModel('Users');

        $data = $this->request->getData();

        //---------------------------- DATI SOCIETA -----------------------------
        $datiSocieta = $data['datiSocieta'];
        $competition = $data['competition'];

        //----------------------------  PDF  -------------------------------------
        $dataPdf = base64_decode($data['pdf']);
        $autodichiarazionePdf = base64_decode($data['autodichiarazione']);

        $filename = $competition['nome_gara'] . "/iscrizioni_" . $datiSocieta['nome_societa'] . ".pdf";
        $filenameCertificati = $competition['nome_gara'] . "/autodichiarazione_" . $datiSocieta['nome_societa'] . ".pdf";

        $mediaupload_success = file_put_contents($filename, $dataPdf);
        $mediaupload_success = file_put_contents($filenameCertificati, $autodichiarazionePdf);


        //-----------------------------  EXCEL  -------------------------------------
        $list = $data['datiCsv'];
        $filenameCsv = 'iscrizioni_' . $datiSocieta['nome_societa'] . '.csv';
        $fp = fopen($filenameCsv, 'w');
        foreach ($list as $fields) {

            fputcsv($fp, $fields, ";");
        }
        fclose($fp);

        if (!file_exists($competition['nome_gara'])) {
            mkdir($competition['nome_gara'], 0777, true);
        }     



        //-------------------------------------- INVIO MAIL EXCEL  --------------------------------------
        $user = $this->Users->get(1);

        $destinatariExcel = array($user['email']);

        if (!$competition['richieste']['tabsManagement']) {
            array_push($destinatariExcel, $competition['email']);
        }

        $testoMail = "Nuova iscrizione effettuata da " . $datiSocieta['nome_societa'];
        $Email = new Email();
        $Email->from(['info@iscrizionicsenveneto.it' => 'Iscrizioni CSEN Veneto'])
            ->bcc($destinatariExcel)
            ->subject('Nuova iscrizione ' . $datiSocieta['nome_societa'] . ' - ' . $competition['nome_gara'])
            ->attachments($filenameCsv)
            ->send($testoMail);



        //------------------------------------ INVIO MAIL PDF RIEPILOGO -------------------------------------
        $destinatariPdf = array($competition['email'], $user['email'], $datiSocieta['mail']);

        $testoSocieta = "Buongiorno, in allegato il riepilogo delle iscrizioni per la manifestazione " . $competition['nome_gara'] . "\r\r Societa: " . $datiSocieta['nome_societa'] . "\r Codice Fiscale: " . $datiSocieta['CF'] . "\r Responsabile: " . $datiSocieta['responsabile'] . "\r Telefono: " . $datiSocieta['telefono'] . "\r Email: " . $datiSocieta['mail'] . "\r coach: " . $datiSocieta['coach'] . "\r\r Inviamo anche il modulo di autocertificazione per l'affiliazione e i certificati medici che deve essere firmato e consegnato in sede di gara, o inviato via email al responsabile di gara.";

        if ($data['note'] != '') {
            $testoSocieta .= "\r \r NOTE: \r " . $data['note'];
        }

        $EmailRiepilogo = new Email();
        $EmailRiepilogo->from(['info@iscrizionicsenveneto.it' => 'Iscrizioni CSEN Veneto'])
            ->bcc($destinatariPdf)
            ->subject('Riepilogo iscrizione ' . $datiSocieta['nome_societa'] . ' - ' . $competition['nome_gara'])
            ->attachments([$filename, $filenameCertificati])
            ->send($testoSocieta);


        $this->apiResponse['success'] = true;
    }*/
}
