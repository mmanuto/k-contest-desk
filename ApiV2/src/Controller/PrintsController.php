<?php
declare(strict_types=1);

namespace App\Controller;
use RestApi\Controller\ApiController;
use Cake\Core\Configure;
use TournamentPdf\Utility\PassPdf;
use TournamentPdf\Utility\BracketPdf;
use TournamentPdf\Utility\AthleteListPdf;
use TournamentPdf\Utility\CoverPagePdf;

/**
 * Results Controller
 *
 * @method \App\Model\Entity\Result[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class PrintsController extends ApiController

{

public function downloadCoverPages()
    {
        $this->loadModel('Competitions');
        $this->loadModel('Categorycodes');
        // 1. Dati Gara
        $competitionId = $this->request->getQuery('competitionId'); // O dinamico
        
        // Recuperiamo i dati della competizione (nome, data)
        $competitionData = $this->Competitions->get($competitionId);

        // 2. Recupera Categorie
        // Qui non servono gli atleti, basta la lista delle categorie
        $categories = $this->Categorycodes->find()
            ->select($this->Categorycodes)
            ->select(['n_athletes' => 'COUNT("AthleteInscriptions".id)'])    
            ->innerJoinWith('"AthleteInscriptions"')
            ->where([
                'Categorycodes.codiceTipoCategorie' => 3, // O $competitionData['codiceTipoCategorie']
                '"AthleteInscriptions".deleted' => 0
            ])
            ->group(['Categorycodes.id'])    
            ->order(['Categorycodes.order_number' => 'ASC'])    
            ->all();

        // 3. Logo
        $logoPath = WWW_ROOT . 'resources' . DS . 'logoCsen.jpg';
        // Nota: controlla che il percorso fisico sia corretto sul server (WWW_ROOT punta a webroot/)

        // 4. Genera PDF
        $pdfString = CoverPagePdf::generate($categories, $competitionData, $logoPath);

        // 5. Download
        return $this->response->withType('application/pdf')
                              ->withStringBody($pdfString)
                              ->withDownload('Frontespizi.pdf');
    }

    public function downloadAthleteList()
    {
        $this->loadModel('Categorycodes');
        $this->loadModel('AthleteInscriptions');

        // 1. Recupera ID gara (dalla sessione o dalla request)
        $competitionId = 'GTTMLT-2026'; // O $this->request->getQuery('competition_id');

        // 2. Recupera Categorie + Atleti + Club
        // Questa query sostituisce la chiamata "getCategoriesWithAthletes" del frontend
        $categories = $this->Categorycodes->find()
            ->contain([
                // Carichiamo le iscrizioni attive e ordinate
                'AthleteInscriptions' => function ($q) {
                    return $q->where(['"AthleteInscriptions".deleted' => 0])
                             ->contain(['Athletes.Clubs'])
                             ->order(['Clubs.id', 'Athletes.id']);
                }
            ])
            // Opzionale: filtrare per tipo (es. solo kumite) se passato via parametro
            ->order(['Categorycodes.order_number' => 'ASC']) 
            ->all();

        // 3. Configura parametri grafici
        $competitionName = $this->request->getQuery('nomeGara');

        $logoPath = WWW_ROOT . 'resources' . DS . 'logoCsen.jpg';
        // 4. Genera PDF usando il Plugin
        $pdfString = AthleteListPdf::generate($categories, $competitionName, $logoPath);

        // 5. Download
        $response = $this->response->withType('application/pdf');
        $response = $response->withStringBody($pdfString);
        return $response->withDownload('Sintetico_Categorie.pdf');
    }

    // ========================================================================
    // STAMPA TABELLONI KUMITE
    // ========================================================================
    public function printBracketPdf() {
        
        $this->loadModel('Categorycodes');
        $this->loadModel('AthleteInscriptions');

        $data = $this->request->getData();
        $pdf = new BracketPdf('P', 'mm', 'A4', true, 'UTF-8', false);
        // Imposta margini più stretti per sfruttare tutto il foglio
        $pdf->SetMargins(20, 25, 10); // Sx, Top, Dx
        $pdf->SetAutoPageBreak(false); // Importante: gestiamo noi i cambi pagina se servono
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(false);

        // Determina la lista delle categorie da stampare
        $categoriesToPrint = [];

        if (!empty($data['categoryId'])){

            // Caso Singolo
            $categoriesToPrint[] = $this->Categorycodes->get($data['categoryId']);
            $filename = 'SingleKumite.pdf';

        }else{

            $categoriesToPrint = $this->Categorycodes->find()
                ->where([
                    'specialita IN' => ['Kumite', 'Kumite U12']
                    ])
                ->order(['order_number' => 'ASC'])
                ->toArray();
            $filename = 'Kumite_All.pdf';
        }

        foreach ($categoriesToPrint as $category) {

            // Contiamo gli iscritti PRIMA di aggiungere la pagina
            $count = $this->AthleteInscriptions->find()
                ->where(['categorycode_id' => $category->id, 'deleted' => 0])
                ->count();
            
            if ($count == 0) {
            continue; // Salta questa categoria, non crea la pagina
        }
            $headerText = $category->id.' - '.$category->categoria . ' ' .
                        $category->sesso . ' ' . 
                        $category->grado . ' ' . 
                        $category->cat_peso;
                        
            $pdf->headerTitle = $headerText; 

            $pdf->AddPage();
            
            // Passiamo l'ID e l'oggetto $pdf per disegnare
            $this->printSingleBracketPdf($category->id, $pdf);
        }

        // In CakePHP restituisci la response per forzare il download corretto
        $pdfContent = $pdf->Output($filename, 'S');

        return $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withStringBody($pdfContent);
    }


    private function printSingleBracketPdf($categoryId, $pdf) {
        
        // 1. Recupera gli iscritti
        $inscriptions = $this->AthleteInscriptions->find()
            ->contain(['Athletes.Clubs'])
            ->where(['categorycode_id' => $categoryId, 'deleted' => 0])
            ->order(['Clubs.id', 'Athletes.id']) 
            ->toArray(); // Importante convertire in array
        
        $count = count($inscriptions);

        // 2. SOLUZIONE GIRONE ALL'ITALIANA (3 Atleti)
        if ($count == 3) {
            // Chiama la nuova funzione specifica per il girone
            $pdf->drawRoundRobin3($inscriptions);
            return; // Esci, non disegnare l'albero
        }

        // 2. Ottieni la struttura virtuale
        $brackets = $this->_getVirtualBracket($inscriptions);

        // --- CONFIGURAZIONE SINCRONIZZATA ---
        $boxHeight = 7; 
        $internalGap = 4; // DEVE ESSERE UGUALE A $pdf->matchGap
        $pdf->matchGap = $internalGap; // Passiamo il valore al PDF per sicurezza
        
        // Parametri pagina
        $pageWidth = $pdf->getPageWidth(); 
        $pageHeight = $pdf->getPageHeight();
        $xStart = 10;
        $yStart = 35;
        
        // Larghezza colonne
        $margins = 20;    
        $usableWidth = $pageWidth - $margins;
        $maxRound = max(array_keys($brackets));
        $colWidth = $usableWidth / $maxRound; 
        if ($colWidth > 60) $colWidth = 60;

        $boxWidth = $colWidth - 17; 
        $pdf->setBoxWidth($boxWidth);
        
        $coordinates = []; 

        // --- DISEGNA ROUND 1 ---
        $round1 = $brackets[1];
        $countR1 = count($round1);
        $i = 0;
        
        // Spazio per ripescaggi: Alziamo il limite (es. 80mm dal fondo)
        $footerHeight = 80; // Aumentato per lasciare più spazio sotto
        $availableHeight = $pageHeight - $yStart - $footerHeight;

        // Gap tra i nomi nello stesso match (deve corrispondere a quello in drawMatch)
        $matchBlockHeight = ($boxHeight * 2) + $internalGap;

        // Calcolo gap verticale tra i match
        if ($countR1 > 1) {
            $totalBlockSpace = $countR1 * $matchBlockHeight;
            $remainingSpace = $availableHeight - $totalBlockSpace;
            $verticalGap = $remainingSpace / ($countR1 - 1);
            
            // Limiti estetici
            if ($verticalGap < 4) $verticalGap = 4; 
            if ($verticalGap > 18) $verticalGap = 18;
        } else {
            $verticalGap = 10;
        }

        foreach ($round1 as $matchNum => $match) {
            $x = $xStart;
            $y = $yStart + ($i * ($matchBlockHeight + $verticalGap)); 
            
            $coords = $pdf->drawMatch($x, $y, $match['aka_name'], $match['ao_name']);
            $coordinates[1][$matchNum] = $coords;
            $i++;
        }

        $treeBottomY = 0;

        // --- DISEGNA ROUND SUCCESSIVI ---
        // Variabile per tracciare l'ultimo punto Y disegnato (utile per i ripescaggi)
        for ($r = 2; $r <= $maxRound; $r++) {
            foreach ($brackets[$r] as $matchNum => $match) {
                $prev1 = ($matchNum * 2) - 1;
                $prev2 = ($matchNum * 2);
                
                if (isset($coordinates[$r-1][$prev1]) && isset($coordinates[$r-1][$prev2])) {
                    $p1 = $coordinates[$r-1][$prev1];
                    $p2 = $coordinates[$r-1][$prev2];
                    
                    $newX = $xStart + (($r - 1) * $colWidth);
                    
                    // CALCOLO CENTRATURA PERFETTA
                    // Vogliamo che la linea orizzontale (a midY) colpisca esattamente
                    // lo spazio vuoto tra i due nomi del nuovo match.
                    $midY = ($p1['y'] + $p2['y']) / 2;
                    
                    // Formula corretta: Centro - (AltezzaBox + MetàGap)
                    // BoxHeight (7) + HalfGap (1) = 8mm
                    $newY = $midY - (($boxHeight * 1.5) + ($internalGap / 2)); 
                
                    $coords = $pdf->drawMatch($newX, $newY, $match['aka_name'], $match['ao_name']);
                    $coordinates[$r][$matchNum] = $coords;
                    
                    // Disegna connessione
                    // Ordina i punti per avere pTop (alto) e pBottom (basso)
                    $pTop = ($p1['y'] < $p2['y']) ? $p1 : $p2;
                    $pBottom = ($p1['y'] < $p2['y']) ? $p2 : $p1;
                    $pdf->drawConnection($pTop, $pBottom, $newX);

                    if ($r == $maxRound) {
                    $pdf->drawWinnerLine($coords);
                }

                    $currentBottom = $newY + ($boxHeight * 2) + $internalGap;
                    if ($currentBottom > $treeBottomY) $treeBottomY = $currentBottom;
                }
            }
        }

        // --- DISEGNA TABELLA RIPESCAGGI VUOTA (In basso) ---
        // Calcola una posizione Y sicura. 
        // Se il tabellone è corto, mettila a 150. Se è lungo, mettila sotto l'ultimo match.
        
        $idealY = $pageHeight - 70; 

        // Se l'albero finisce dopo la posizione ideale, spostati sotto l'albero
        $repechageY = max($idealY, $treeBottomY + 15);
        
        // Se sfori la pagina, nuova pagina
        if ($repechageY > ($pageHeight - 40)) {
            $pdf->AddPage();
            $repechageY = 30;
        }
        
        $pdf->drawEmptyRepechageGrid($repechageY);
    }

    private function _getVirtualBracket($atleti_inscriptions) {
        $count = count($atleti_inscriptions);
        
        // 1. Calcola dimensione tabellone (4, 8, 16, 32, 64...)
        $bracketSize = (int) pow(2, ceil(log($count, 2)));
        if ($bracketSize < 4) $bracketSize = 4; // Minimo tabellone da 4

        // 2. Padding con NULL (BYE)
        // È CRUCIALE usare array_values per resettare le chiavi numeriche se arrivano sporche
        $atleti = array_values($atleti_inscriptions);
        $atletiPaddati = array_pad($atleti, $bracketSize, null);

        // 3. Logica di Ordinamento
        $half = $bracketSize / 2;
        $indicesOrder = [];
        
        // Prima metà (Dispari: 0, 2, 4...) - Seconda metà (Pari: 1, 3, 5...)
        for ($i = 0; $i < $half; $i += 2) $indicesOrder[] = $i;
        for ($i = 1; $i < $half; $i += 2) $indicesOrder[] = $i;

        // 4. Costruiamo la struttura dati per il PDF
        $virtualMatches = [];
        $totalRounds = (int) log($bracketSize, 2); // Es. 32 atleti = 5 round

        // --- GENERAZIONE ROUND 1 (Con i Nomi) ---
        $matchCounter = 1;
        foreach ($indicesOrder as $i) {
            $aka = $atletiPaddati[$i] ?? null;
            $ao  = $atletiPaddati[$i + $half] ?? null;

            $virtualMatches[1][$matchCounter] = [
                'round' => 1,
                'number' => $matchCounter,
                'aka_name' => $aka ? $aka->athlete->cognome . ' ' . $aka->athlete->nome : '', // O lascia vuoto se preferisci
                'ao_name'  => $ao  ? $ao->athlete->cognome  . ' ' . $ao->athlete->nome  : '',
                'aka_club' => $aka ? $aka->athlete->club->club_name : '', // Utile per la stampa
                'ao_club'  => $ao  ? $ao->athlete->club->club_name : '',
            ];
            $matchCounter++;
        }

        // --- GENERAZIONE ROUND SUCCESSIVI ---
        // Servono per disegnare le caselle vuote nel PDF
        $matchesInRound = $bracketSize / 2; 

        for ($r = 2; $r <= $totalRounds; $r++) {
            $matchesInRound /= 2; // Dimezza i match (16 -> 8 -> 4...)
            for ($k = 1; $k <= $matchesInRound; $k++) {

                // INDIVIDUA I MATCH "GENITORI" DEL ROUND PRECEDENTE
                // Il match K del round R proviene da:
                // - Aka: Match (K*2)-1 del round R-1
                // - Ao:  Match (K*2)   del round R-1
                
                $prevMatchAkaIdx = ($k * 2) - 1;
                $prevMatchAoIdx  = ($k * 2);
                
                $prevMatchAka = $virtualMatches[$r-1][$prevMatchAkaIdx];
                $prevMatchAo  = $virtualMatches[$r-1][$prevMatchAoIdx];

                // --- SIMULAZIONE AVANZAMENTO ---
            
                // 1. Chi va nello slot AKA (Rosso)?
                // Controlliamo il match genitore ($prevMatchAka):
                // Se c'è un nome in AKA e NESSUNO in AO -> Vince AKA per BYE
                $currentAkaName = '';
                $currentAkaClub = '';
                
                if (!empty($prevMatchAka['aka_name']) && empty($prevMatchAka['ao_name'])) {
                    // BYE! Passa il turno automaticamente
                    $currentAkaName = $prevMatchAka['aka_name'];
                    $currentAkaClub = $prevMatchAka['aka_club'];
                }

                // 2. Chi va nello slot AO (Blu)?
                // Controlliamo il match genitore ($prevMatchAo):
                // Se c'è un nome in AKA e NESSUNO in AO -> Vince AKA per BYE
                // (Nota: in un tabellone standard il vincitore di un match è sempre 'aka' se l'altro è vuoto, 
                // perché nel round 1 abbiamo riempito prima aka e poi ao)
                $currentAoName = '';
                $currentAoClub = '';
                
                if (!empty($prevMatchAo['aka_name']) && empty($prevMatchAo['ao_name'])) {
                    // BYE! Passa il turno automaticamente
                    $currentAoName = $prevMatchAo['aka_name'];
                    $currentAoClub = $prevMatchAo['aka_club'];
                }

                // CREA IL MATCH VIRTUALE
                $virtualMatches[$r][$k] = [
                    'round' => $r,
                    'number' => $k,
                    'aka_name' => $currentAkaName, // Sarà pieno se c'era un BYE, vuoto se si deve combattere
                    'ao_name'  => $currentAoName,
                    'aka_club' => $currentAkaClub,
                    'ao_club'  => $currentAoClub
                ];
            }
        }

        return $virtualMatches;
    }

    // ========================================================================
    // DOWNLOAD EXCEL ACCORPAMENTI
    // ========================================================================



    // ========================================================================
    // STAMPA CARTELLINI
    // ========================================================================

    public function downloadAllPassesZip() {
        $this->loadModel('AthleteInscriptions');
        $this->loadModel('Categorycodes');

        // 1. Definiamo le tue liste (Hardcoded o da DB)
        $ageCategories = ['UNDER 6', 'UNDER 8', 'UNDER 10', 'UNDER 12'];
        $beltCategories = ['Bianca', 'Gialla/Arancio', 'Verde/Blu/Marrone'];

        // 2. Prepariamo lo ZIP
        $zip = new \ZipArchive();
        $tmpFile = tempnam(sys_get_temp_dir(), 'pass_zip');
        
        if ($zip->open($tmpFile, \ZipArchive::CREATE) !== TRUE) {
            $this->Flash->error("Impossibile creare il file ZIP");
            return $this->redirect(['action' => 'index']);
        }

        $filesAdded = 0;

        // 3. DOPPIO CICLO (Combinazione Età x Cintura)
        foreach ($ageCategories as $age) {
            foreach ($beltCategories as $belt) {
                
                // A. Trova gli ID delle categorie che corrispondono a questa coppia
                // Nota: Adatto la query ai nomi delle tue colonne (categoria, grado)
                // Usa il LIKE per flessibilità (es. "Gialla/Arancio" trova anche "Gialla")
                $categoryIds = $this->Categorycodes->find()
                    ->select(['id'])
                    ->where([
                        'categoria LIKE' => "%$age%", // Es. Cerca 'Under 6'
                        'grado LIKE' => "%$belt%",     // Es. Cerca 'Bianca'
                        'specialita NOT LIKE' => "%Kumite%"
                    ])
                    ->extract('id')
                    ->toArray();

                if (empty($categoryIds)) continue; // Nessuna categoria trovata per questa combinazione

                // B. Trova gli atleti iscritti a queste categorie
                $inscriptions = $this->AthleteInscriptions->find()
                    ->contain(['Athletes.Clubs', 'Categorycodes'])
                    ->where([
                        'categorycode_id IN' => $categoryIds,
                        'deleted' => 0
                    ])
                    ->order(['Clubs.id' => 'ASC'])
                    ->toArray();

                // Se non ci sono atleti, saltiamo la generazione del file
                if (empty($inscriptions)) continue;

                // C. Genera il contenuto del PDF per questa combinazione

                // Raggruppiamo le iscrizioni per Atleta per evitare duplicati di pass
                $athletesData = [];
                foreach ($inscriptions as $ins) {
                    $athletesData[$ins->athlete_id]['info'] = $ins; // Dati generali (nome, club)
                    // Salviamo il codice della specialità (es. 'PERCORSO' => 'PER11')
                    $specName = strtoupper($ins->categorycode->specialita);
                    $athletesData[$ins->athlete_id]['codes'][$specName] = $ins->categorycode->id; 
                }

                // Passiamo un titolo personalizzato per il file
                $pdfTitle = "$age - $belt";
                $logoPath = WWW_ROOT . 'resources' . DS . 'logoCsen.jpg';
                $pdfContent = PassPdf::generatePdfForPass($athletesData, $pdfTitle, $logoPath);

                // D. Aggiungi il file allo ZIP
                // Puliamo il nome file da caratteri strani (es. / diventa -)
                $cleanAge = str_replace(['/', '\\', ' '], '-', $age);
                $cleanBelt = str_replace(['/', '\\', ' '], '-', $belt);
                $filename = "Pass_{$cleanAge}_{$cleanBelt}.pdf";

                $zip->addFromString($filename, $pdfContent);
                $filesAdded++;
            }
        }

        $zip->close();

        // 4. Scarica lo ZIP
        if ($filesAdded > 0) {
            $response = $this->response->withFile($tmpFile, [
                'download' => true,
                'name' => 'Tutti_Pass_Atleti.zip'
            ]);
            
            // Importante: Elimina il file temporaneo dopo l'invio (CakePHP lo gestisce, 
            // ma con withFile su temp a volte serve pulizia, qui ci affidiamo al sistema tmp)
            return $response;
        } else {
            $this->Flash->warning("Nessun atleta trovato per le combinazioni richieste.");
            return $this->redirect(['action' => 'index']);
        }
    }

}