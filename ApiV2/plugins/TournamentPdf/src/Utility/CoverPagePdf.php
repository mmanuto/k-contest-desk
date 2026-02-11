<?php
namespace TournamentPdf\Utility;

use TCPDF;

class CoverPagePdf
{
    public static function generate($categories, $competitionData, $logoPath = null)
    {
        // 1. Setup TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(false); // Disattiviamo auto-break perché disegniamo a coordinate fisse
       
        // 2. Preparazione dati gara
        $nomeGara = $competitionData->nome_gara ?? 'Gara Karate';
        
        // Formattazione Data (gestisce sia stringa che oggetto DateTime di CakePHP)
        $dataGaraRaw = $competitionData->data_gara;
        $dataGara = '';
        if ($dataGaraRaw instanceof \Cake\I18n\FrozenDate || $dataGaraRaw instanceof \DateTime) {
            $dataGara = $dataGaraRaw->format('d/m/Y');
        } else {
            // Fallback se è stringa "2026-01-20"
            $dataGara = date('d/m/Y', strtotime($dataGaraRaw));
        }

        // 3. Ciclo su ogni categoria
        foreach ($categories as $cat) {
            $pdf->AddPage();

            // --- A. LOGO ---
            // Centrato: (210 - 45) / 2 = 82.5
            if ($logoPath && file_exists($logoPath)) {
                $pdf->Image($logoPath, 15, 13, 28, 28, '', '', '', false, 300, '', false, false, 0);
                // Logo Destro (speculare come nel tuo JS)
                $pdf->Image($logoPath, 167, 13, 28, 28, '', '', '', false, 300, '', false, false, 0);
            }

            // --- B. INTESTAZIONE GARA ---
            $pdf->SetFont('helvetica', 'B', 20);
            
            // Nome Gara (Y=77)
            $pdf->SetY(25);
            $pdf->Cell(0, 10, $nomeGara, 0, 1, 'C');

            // Data Gara (Y=87)
            $pdf->SetY(35);
            $pdf->Cell(0, 10, $dataGara, 0, 1, 'C');

            // --- C. DETTAGLI CATEGORIA ---
            $pdf->SetFont('helvetica', 'B', 18);

            // Preparazione stringhe (Gestisce sia Entity che Array)
            $specialita = strtoupper($cat->specialita ?? $cat->specialty ?? '');
            $categoria = strtoupper($cat->categoria ?? $cat->description ?? '');
            $grado = strtoupper($cat->grado ?? $cat->belt ?? '');
            
            $sesso = strtoupper($cat->sesso ?? $cat->gender ?? '');
            $peso = strtoupper($cat->cat_peso ?? $cat->weight_category ?? '');
            
            if (!empty($peso)) {
                $sesso .= ' ' . $peso;
            }

            // Stampa righe centrate
            $pdf->SetY(60);
            $pdf->Cell(0, 10, $specialita, 0, 1, 'C');

            $pdf->SetY(70);
            $pdf->Cell(0, 10, $categoria, 0, 1, 'C');

            $pdf->SetY(80);
            $pdf->Cell(0, 10, $sesso, 0, 1, 'C');

            $pdf->SetY(90);
            $pdf->Cell(0, 10, $grado, 0, 1, 'C');

            // --- D. FOOTER (Classifica e Firme) ---
            $pdf->SetFont('helvetica', '', 11);
            $lMargin = 15;

            // Classifica
            $pdf->Text($lMargin, 130, 'CLASSIFICA:');
            
            $dots = ' ...................................................................................................................................';
            
            $pdf->Text($lMargin, 139, '1° ' . $dots);
            $pdf->Text($lMargin, 149, '2° ' . $dots);
            $pdf->Text($lMargin, 159, '3° ' . $dots);
            $pdf->Text($lMargin, 169, '3° ' . $dots);

            // Firme
            $pdf->Text($lMargin, 189, 'PRESIDENTI DI GIURIA:');
            $pdf->Text($lMargin, 200, $dots);

            $pdf->Text($lMargin, 218, 'UFFICIALI DI GARA:');
            $pdf->Text($lMargin, 228, $dots);
            $pdf->Text($lMargin, 238, $dots);
            $pdf->Text($lMargin, 248, $dots);
        }

        return $pdf->Output('Frontespizi.pdf', 'S');
    }
}