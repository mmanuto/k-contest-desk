<?php
namespace TournamentPdf\Utility;

use TCPDF;

class AthleteListPdf
{
    public static function generate($categories, $competitionName, $logoPath = null)
    {
        // 1. Setup TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15); // Gestisce il salto pagina automatico se la lista atleti è lunga

        // 2. Ciclo su ogni categoria
        foreach ($categories as $cat) {

            $atleti = $cat->athletes ?? $cat->athlete_inscriptions;

            // Se non ci sono atleti, saltiamo questa categoria e passiamo alla prossima
            if (empty($atleti) || count($atleti) == 0) {
                continue;
            }
            // Aggiunge pagina per ogni categoria
            $pdf->AddPage();

            // --- A. HEADER (Loghi e Titolo) ---
            // Logo Sinistro
            if ($logoPath && file_exists($logoPath)) {
                $pdf->Image($logoPath, 15, 13, 28, 28, '', '', '', false, 300, '', false, false, 0);
                // Logo Destro (speculare come nel tuo JS)
                $pdf->Image($logoPath, 167, 13, 28, 28, '', '', '', false, 300, '', false, false, 0);
            }

            $pdf->SetY(35);
            $pdf->SetFont('helvetica', 'B', 22);
            $pdf->Cell(0, 10, $competitionName, 0, 1, 'C');

            // --- B. TABELLA INFO CATEGORIA (Centrale) ---
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Ln(10); // Spazio dopo il titolo

            // Logica identica al tuo JS: controlla prime 3 lettere del codice
            $codePrefix = substr($cat->category_code_id ?? $cat->id, 0, 3);
            $isKumite = in_array($codePrefix, ['KUA', 'KUG']);
            $isCombinata = in_array($codePrefix, ['PAL', 'PER', 'KAG']);

            // Prepariamo i dati della categoria
            // Nota: Adatta i nomi delle proprietà ($cat->name, $cat->belt...) al tuo oggetto CakePHP
            $infoData = [
                ['Codice', $cat->id], // Gestisco sia oggetto che array
                ['Specialità', $cat->specialita],
                ['Categoria', $cat->categoria],
                ['Cintura', $cat->grado],
                ['Sesso', $cat->sesso],
                [$isCombinata ? 'Colore' : 'Peso', $isCombinata? 'colore' : $cat->cat_peso]
            ];

            // Disegniamo la tabellina info centrata
            $wLabel = 30;
            $wValue = 60;
            $tableWidth = $wLabel + $wValue;
            $startX = (210 - $tableWidth) / 2; // 210 è larghezza A4

            foreach ($infoData as $row) {
                $pdf->SetX($startX);
                $pdf->Cell($wLabel, 6, $row[0], 1, 0, 'L'); // Etichetta
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell($wValue, 6, $row[1], 1, 1, 'L'); // Valore
                $pdf->SetFont('helvetica', '', 10);
            }

            // --- C. LOGICA COLONNE (Kumite vs Altro) ---
            $pdf->Ln(10); // Spazio prima della lista
            
            

            // Definizione larghezze colonne
            if ($isKumite) {
                // Nr, Atleta, Società, Peso
                $cols = [15, 65, 80, 20]; 
                $headers = ['Nr.', 'Atleta', 'Società', 'Peso'];
            } else {
                // Nr, Atleta, Società
                $cols = [15, 80, 85];
                $headers = ['Nr.', 'Atleta', 'Società'];
            }

            // --- D. HEADER TABELLA ATLETI ---
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(240, 240, 240);
            
            foreach ($headers as $i => $h) {
                $pdf->Cell($cols[$i], 7, $h, 1, 0, 'C', 1);
            }
            $pdf->Ln();

            // --- E. RIGHE ATLETI ---
            $pdf->SetFont('helvetica', '', 10);
            
            $count = 1;

            foreach ($atleti as $iscrizione) {
                // Recupera dati atleta (dipende da come è strutturata la tua query contain)
                // Caso 1: Array piatto dal frontend
                // Caso 2: Entity CakePHP ($iscrizione->athlete->surname...)
                
                // Adattatore rapido per capire se stiamo usando entità Cake
                $nome = '';
                $club = '';
                $peso = '';

                if (isset($iscrizione->athlete)) {
                    $nome = $iscrizione->athlete->cognome . ' ' . $iscrizione->athlete->nome;
                    $club = $iscrizione->athlete->club->club_name ?? '';
                    $peso = $iscrizione->athlete->peso ?? '';
                } else {
                    // Fallback se i dati sono piatti
                    $nome = ($iscrizione['cognome'] ?? '') . ' ' . ($iscrizione['nome'] ?? '');
                    $club = $iscrizione['club_name'] ?? '';
                }

                // Stampa riga
                // Cella NR
                $pdf->Cell($cols[0], 7, $count++, 1, 0, 'C');
                // Cella Nome
                $pdf->Cell($cols[1], 7, substr($nome, 0, 35), 1, 0, 'L');
                // Cella Società
                $pdf->Cell($cols[2], 7, substr($club, 0, 35), 1, 0, 'L');
                
                // Cella Peso (se Kumite)
                if ($isKumite) {
                    $pdf->Cell($cols[3], 7, $peso, 1, 0, 'C');
                }
                
                $pdf->Ln();
            }
        }

        return $pdf->Output('elenco_atleti.pdf', 'S');
    }
}