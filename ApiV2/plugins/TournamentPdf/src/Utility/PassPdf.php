<?php
namespace TournamentPdf\Utility;

use TCPDF;

class PassPdf
{
    /**
     * Genera il PDF dei Pass Atleti (A6)
     */
    public static function generatePdfForPass($inscriptions, $groupTitle, $logoFile = null)
    {
        // Inizializza TCPDF
        $pdf = new \TCPDF('P', 'mm', 'A6', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 5);

        foreach ($inscriptions as $atleta) {
        $ins = $atleta['info'];
        $codes = $atleta['codes'];    
        
        $pdf->AddPage();
            
            // Recupero Dati
            $nome = strtoupper($ins->athlete->cognome . ' ' . $ins->athlete->nome);
            $societa = $ins->athlete->club->club_name;
            $cintura = $ins->athlete->grado;
            $catName = $ins->categorycode->categoria . ' ' . $ins->categorycode->cat_peso;

            // --- DISEGNO BORDO ESTERNO SPESSO ---
            $pdf->SetLineWidth(0.3); // Bordo esterno spesso
            $pdf->Rect(5, 5, 95, 138);

            // Intestazione Gruppo (Utile per capire che file è stampando)
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetXY(10, 8);
            $pdf->Cell(85, 5, "Gruppo: $groupTitle", 0, 1, 'C');

            if (file_exists($logoFile)) {
                
                // Logo Sinistro (X=8, Y=16, Larghezza=16mm)
                $pdf->Image($logoFile, 9, 13, 13, '', '', '', '', false, 300, '', false, false, 0);
                
                // Logo Destro (X=81, Y=16, Larghezza=16mm)
                $pdf->Image($logoFile, 82, 13, 13, '', '', '', '', false, 300, '', false, false, 0);
            }

            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetY(32);
            $pdf->MultiCell(85, 8, "TROFEO GATTAMELATA 2025", 0, 'C');
            
            $pdf->Ln(2);
            $pdf->Line(20, $pdf->GetY(), 85, $pdf->GetY());
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->MultiCell(85, 8, $nome, 0, 'C');
            
            $pdf->SetFont('helvetica', 'I', 11);
            $pdf->MultiCell(85, 6, $societa, 0, 'C');

            $pdf->Ln(4);

            $pdf->SetLineWidth(0.1);

            // Tabella Dati
            $pdf->SetFillColor(240, 240, 240); 
            $pdf->SetFont('helvetica', '', 10);
            
            $wLabel = 30; $wValue = 55; $hRow = 6;
            
            $rows = [
                "Cintura" => $cintura,
                "Categoria" => $catName,
                "Genere" => $ins->athlete->sesso,
                "Data di Nascita" => $ins->athlete->data_nascita
            ];

            foreach($rows as $label => $val) {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell($wLabel, $hRow, $label, 1, 0, 'L', 1);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell($wValue, $hRow, substr($val, 0, 28), 1, 1, 'L', 0);
            }

            $pdf->Ln(8); // Spazio tra le due tabelle

            // Definisco larghezze colonne (Totale larghezza scrivibile su A6 ~ 85mm)
            // 34mm per il nome prova, 17mm per le celle numeri
            $wSpec = 31; 
            $wNum = 18; 
            $hScoreRow = 8; // Altezza righe punteggi

            // Intestazione Tabella Punteggi
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(240, 240, 240); // Grigio chiaro
            
            // Prima riga: Headers
            $pdf->Cell($wSpec, $hScoreRow, "SPECIALITA'", 1, 0, 'L', 1);
            $pdf->Cell($wNum, $hScoreRow, "PUNTI", 1, 0, 'L', 1);
            $pdf->Cell($wNum, $hScoreRow, "PENALITA'", 1, 0, 'L', 1);
            $pdf->Cell($wNum, $hScoreRow, "TOTALI", 1, 1, 'L', 1);

            // Righe Attività
            $activities = [
                'PERCORSO' => 'PERCORSO',
                'PALLONCINO' => 'PALLONCINO',
                'KATA' => 'KATA'
            ];
            
            $pdf->SetFont('helvetica', '', 8);
            foreach($activities as $key => $label) {

                // Cerchiamo se l'atleta ha un codice per questa attività
                // Usiamo una ricerca flessibile per trovare il codice corrispondente
                $currentCode = "";
                foreach($codes as $spec => $c) {
                    if(strpos($spec, $key) !== false) {
                        $currentCode = $c;
                        break;
                    }
                }
                $pdf->Cell($wSpec, $hScoreRow, $currentCode. ' - '.$label, 1, 0, 'L', 0);
                $pdf->Cell($wNum, $hScoreRow, "", 1, 0, 'C', 0);
                $pdf->Cell($wNum, $hScoreRow, "", 1, 0, 'C', 0);
                $pdf->Cell($wNum, $hScoreRow, "", 1, 1, 'C', 0);
            }

        }

        // Ritorna il PDF come stringa grezza ('S')
        return $pdf->Output('doc.pdf', 'S');
    }
}