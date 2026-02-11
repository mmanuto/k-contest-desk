<?php
namespace TournamentPdf\Utility;

use TCPDF;

class BracketPdf extends TCPDF {

    // Configurazioni layout
    private $startX = 10;
    private $startY = 20;
    private $boxHeight = 7;  // Altezza riga nome
    private $boxWidth = 35;  // Larghezza riga nome
    public $headerTitle = '';
    public $matchGap = 4;

    public function Header() {
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, $this->headerTitle, 'B', 1, 'C');
        $this->Ln(2);
    }

    public function setBoxWidth($width) {
        $this->boxWidth = $width;
    }
    public function getBoxHeight() {
        return $this->boxHeight;
    }

    /**
     * Disegna un singolo incontro e ritorna la coordinata Y del centro (per il connettore)
     */
    public function drawMatch($x, $y, $akaName, $aoName) {
        
        $this->SetFont('helvetica', '', 9);
        $this->SetLineWidth(0.3); 
        $this->SetLineStyle(array('width' => 0.3, 'color' => array(0, 0, 0)));

        // --- BOX AKA (Sopra) ---
        $this->SetXY($x, $y);
        $textAka = substr($akaName, 0, 25);
        $this->Cell($this->boxWidth, $this->boxHeight, $textAka, 'B', 0, 'L');

        // --- BOX AO (Sotto) ---
        $yAo = $y + $this->boxHeight + $this->matchGap; 
        $this->SetXY($x, $yAo); 
        $textAo = substr($aoName, 0, 25);
        $this->Cell($this->boxWidth, $this->boxHeight, $textAo, 'B', 0, 'L');

        // --- CONNETTORE (Linea verticale per il turno successivo) ---
        // Disegna una linea verticale alla fine dei nomi che unisce il nome sopra e quello sotto
        $lineX = $x + $this->boxWidth;
        $yTop = $y + $this->boxHeight;       // Livello riga sopra
        $yBottom = $yAo + $this->boxHeight;  // Fine riga sotto
        
        $this->Line($lineX, $yTop, $lineX, $yBottom);
        
        // Centro geometrico del gancetto
        $centerY = ($yTop + $yBottom) / 2;
        
        return ['x' => $lineX, 'y' => $centerY];
    }

    /**
     * Disegna una linea di connessione tra due match e il loro vincitore
     */
    public function drawConnection($pTop, $pBottom, $nextRoundX) {
        $this->SetLineWidth(0.3);
        
        $this->Line($pTop['x'], $pTop['y'], $pTop['x'], $pBottom['y']); // Linea verticale che unisce i due match precedenti
        $midY = ($pTop['y'] + $pBottom['y']) / 2;
        $this->Line($pTop['x'], $midY, $nextRoundX, $midY); // Linea orizzontale verso il match del prossimo round
    }

    /**
     * Disegna la linea del vincitore finale
     * @param array $coords Le coordinate ['x', 'y'] restituite da drawMatch
     */
    public function drawWinnerLine($coords) {
        $this->SetLineWidth(0.3);
        $this->SetLineStyle(array('width' => 0.3, 'color' => array(0, 0, 0))); // Linea solida
        
        $length = 20; // Lunghezza della riga per il vincitore
        
        // Disegna la linea orizzontale che parte dal centro del gancetto
        $this->Line($coords['x'], $coords['y'], $coords['x'] + $length, $coords['y']);
        
        // (Opzionale) Aggiunge la scritta "1° Classificato" piccola sopra la riga
        $this->SetFont('helvetica', '', 7);
        $this->SetXY($coords['x'] + 2, $coords['y'] - 4);
        
    }
    
    /**
     * Disegna la tabella per 3 Atleti (Girone all'italiana)
     */
    public function drawRoundRobin3($inscriptions) {
        $this->SetFont('helvetica', '', 10);
        $this->SetLineWidth(0.3);
        $this->SetLineStyle(array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))); // Assicura solido

        $yStart = 45;
        $this->SetXY(10, $yStart);

        // --- 1. TABELLA ELENCO ATLETI ---
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(10, 8, "#", 1, 0, 'C');
        $this->Cell(80, 8, "ATLETA", 1, 0, 'L');
        $this->Cell(80, 8, "SOCIETÀ", 1, 1, 'L');
        
        $this->SetFont('helvetica', '', 10);
        $i = 1;
        foreach($inscriptions as $ins) {
            $this->SetX(10); // Force start at X=10 for each row
            $nome = $ins->athlete->cognome . ' ' . $ins->athlete->nome;
            $club = $ins->athlete->club->club_name;
            
            $this->Cell(10, 8, $i, 1, 0, 'C');
            $this->Cell(80, 8, substr($nome, 0, 35), 1, 0, 'L');
            $this->Cell(80, 8, substr($club, 0, 35), 1, 1, 'L');
            $i++;
        }

        // --- 2. TABELLA INCONTRI (Fissa per 3 atleti: 1-2, 2-3, 3-1) ---
        $this->Ln(10);
        $this->SetX(10); // Force X
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(0, 8, "INCONTRI GIRONE", 0, 1, 'L');
        
        // Header Incontri
        $this->SetX(10); // Force X
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(15, 7, "Match", 1, 0, 'C', 1);
        $this->Cell(70, 7, "Rosso (AKA)", 1, 0, 'C', 1);
        $this->Cell(15, 7, "Punti", 1, 0, 'C', 1); // Punti Rosso
        $this->Cell(70, 7, "Blu (AO)", 1, 0, 'C', 1);
        $this->Cell(15, 7, "Punti", 1, 1, 'C', 1); // Punti Blu

        // Lista Match Standard (1vs2, 2vs3, 3vs1)
        // Usiamo gli indici dell'array (0, 1, 2)
        $matches = [
            ['n' => 1, 'r' => 0, 'b' => 1], // 1 vs 2
            ['n' => 2, 'r' => 0, 'b' => 2], // 3 vs 1 (o 1 vs 3 a seconda dei regolamenti, metto 3-1 standard rotazione)
            ['n' => 3, 'r' => 1, 'b' => 2], // 2 vs 3
        ];
        
        // Atleti (reset keys per sicurezza)
        $atleti = array_values($inscriptions);
        
        $this->SetFont('helvetica', '', 10);
        
        foreach($matches as $m) {
            $this->SetX(10); // Force start at X=10 for each match row
            $rosso = isset($atleti[$m['r']]) ? $atleti[$m['r']]->athlete->cognome.' '.$atleti[$m['r']]->athlete->nome : '---';
            $blu   = isset($atleti[$m['b']]) ? $atleti[$m['b']]->athlete->cognome.' '.$atleti[$m['b']]->athlete->nome : '---';
            
            $this->Cell(15, 10, $m['n'], 1, 0, 'C');
            $this->Cell(70, 10, substr($rosso, 0, 30), 1, 0, 'L');
            $this->Cell(15, 10, "", 1, 0, 'C'); // Spazio per scrivere punti rosso
            $this->Cell(70, 10, substr($blu, 0, 30), 1, 0, 'L');
            $this->Cell(15, 10, "", 1, 1, 'C'); // Spazio per scrivere punti blu
        }

        // --- 3. CLASSIFICA FINALE ---
        $this->Ln(10);
        $this->SetX(10); // Force X
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(0, 8, "CLASSIFICA", 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 10);
        $this->SetX(10); // Force X
        $this->Cell(20, 8, "1° Posto:", 0, 0, 'L');
        $this->Cell(100, 8, "_____________________________________________", 'B', 1, 'L');
        
        $this->SetX(10); // Force X
        $this->Cell(20, 8, "2° Posto:", 0, 0, 'L');
        $this->Cell(100, 8, "_____________________________________________", 'B', 1, 'L');
        
        $this->SetX(10); // Force X
        $this->Cell(20, 8, "3° Posto:", 0, 0, 'L');
        $this->Cell(100, 8, "_____________________________________________", 'B', 1, 'L');
    }

    public function drawEmptyRepechageGrid($yStart) {
        $this->SetXY(10, $yStart);
        
        // Titoli
        $this->SetFont('helvetica', 'B', 10);
        $this->SetFillColor(230, 230, 230); // Grigio chiaro
        $this->Cell(90, 7, "RIPESCAGGI GRUPPO A", 1, 0, 'C', 1);
        $this->Cell(10, 7, "", 0, 0); 
        $this->Cell(90, 7, "RIPESCAGGI GRUPPO B", 1, 1, 'C', 1);
        
        // Righe vuote per scrivere
        $this->SetFont('helvetica', '', 9);
        $rows = 4;

        // Imposta stile linea TRATTEGGIATA per le righe di scrittura
        $styleDotted = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => '2,2', 'color' => array(0, 0, 0));
        $styleSolid = array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
        
        for($i=0; $i<$rows; $i++) {

            $y = $this->GetY();
            // --- COLONNA A ---
            $this->SetLineStyle($styleSolid); 
            $this->SetX(10);
            $this->Cell(25, 7, "Match ".($i+1), 0, 0, 'L');

            // Linea tratteggiata per scrivere
            $this->SetLineStyle($styleDotted);
            $this->Line(35, $y+6, 95, $y+6); 
            $this->SetX(100); 

            $this->Cell(10, 7, "", 0, 0);
            
            // --- COLONNA B ---
            $this->SetLineStyle($styleSolid);
            $this->Cell(25, 7, "Match ".($i+1), 0, 0, 'L');
            
            $this->SetLineStyle($styleDotted);
            $this->Line(135, $y+6, 195, $y+6);
            
            $this->Ln(7);
        }
        
        // Reset linea solida
        $this->SetLineStyle($styleSolid);
        $this->Ln(5);
        $yBase = $this->GetY();
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(35, 12, "3° CLASSIFICATI:", 0, 0, 'L');
        
        
        $lineStartX = 60;
        $lineEndX = 150;
        $this->SetLineStyle($styleDotted);
        // Linea 1 (Superiore)
        $this->Line($lineStartX, $yBase + 5, $lineEndX, $yBase + 5); 

        // Linea 2 (Inferiore - sotto la prima)
        $this->Line($lineStartX, $yBase + 15, $lineEndX, $yBase + 15); 

        // RESET FINALE OBBLIGATORIO
        $this->SetLineStyle($styleSolid);
    }
}