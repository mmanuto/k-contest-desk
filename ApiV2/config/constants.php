<?php

return [
    // Stati LOGICI (per la colonna 'current_phase')
    "PHASE_PENDING" => 'PENDING', //non aperta
    "PHASE_JUDGING_PANEL" => 'JUDGING_PANEL', //tabella punteggi
    "PHASE_AWAITING_TIEBREAK" => 'AWAITING_TIEBREAK', //attesa spareggio
    "PHASE_AWAITING_BRACKETS" => 'AWAITING_BRACKETS', //attesa tabellone eliminazione diretta
    "PHASE_BRACKETS" => 'BRACKETS', //tabellone eliminazione diretta
    "PHASE_FINALIZED" => 'FINALIZED', //chiusa
    // Stati OPERATIVI (per la colonna 'status')
    "STATUS_BACKLOG" => 'CAT_BACKLOG',
    "STATUS_TODO" => 'CAT_TODO',
    "STATUS_OPEN" => 'CAT_OPEN',
    "STATUS_DOING" => 'CAT_DOING',
    "STATUS_CLOSED" => 'CAT_CLOSED',
    "STATUS_AWARD" => 'CAT_AWARD',
    // Round
    "ROUND_KATA_QUALIFICA" => 1,
    "ROUND_KATA_SPAREGGIO" => 2,    
    "ROUND_MATCH_OTTAVI" => 1,
    "ROUND_MATCH_QUARTI" => 2,
    "ROUND_MATCH_SEMI" => 3,
    "ROUND_MATCH_FINALE" => 4,
    "ROUND_MATCH_RIPESCAGGIO" => 5
];

