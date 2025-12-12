export const colorMapping = {
  AKA: "red",
  AO: "blue",
  PAL: "teal",
  PER: "teal",
  KAG: "teal",
  KIA: "teal",
} as const;

export const prova = {
  PERCORSO: "PER",
  PALLONCINO: "PAL",
  KATA_ADULTI: "KIA",
  KATA_BAMBINI: "KAG",
  KUMITE: "KUA",
  KUMITE_U12: "KUG",
  KATA_SQUADRE: "KAS"
} as const;

export const monitor = {
  ATHLETE_1: "athlete_1",
  ATHLETE_2: "athlete_2",
  KUMITE: "kumite",
  COUNTDOWN: "countdown",
  ACTION: "ACTION",
  ACTION_PAUSE: "PAUSE",
  ACTION_START: "START"
} as const

export const categoryStatus = {
  BACKLOG: "CAT_BACKLOG",
  TODO: "CAT_TODO",
  OPEN: "CAT_OPEN",
  DOING: "CAT_DOING",
  CLOSED: "CAT_CLOSED",
  AWARD: "CAT_AWARD"
} as const;

export const categoryPhase = {
  PENDING: 'PENDING',
  JUDGING_PANEL: 'JUDGING_PANEL',
  TIME_PANEL: 'TIME_PANEL',
  AWAITING_TIEBREAK: 'AWAITING_TIEBREAK',
  AWAITING_BRACKETS: 'AWAITING_BRACKETS',
  BRACKETS: 'BRACKETS',
  JUDGING_FINALIZING: 'JUDGING_FINALIZING', //attesa classifica per palloncino e kata bambini
  TIMED_FINALIZING: 'TIMED_FINALIZING', //attesa classifica per percorso
  BRACKETS_FINALIZING: 'BRACKETS_FINALIZING',
  FINALIZED: 'FINALIZED'
} as const;

export const userRole = {
  ADMIN: "admin",
  TATAMI_USER: "tatami_user"
} as const;

// Mappa per i round di tipo "Punteggio"
export const RoundJudgedPanel = {
  QUALIFICHE: 1,
  SPAREGGIO: 2
} as const;

// Mappa per i round di tipo "Incontro"
export const RoundMatch = {
  OTTAVI: 1,
  QUARTI: 2,
  SEMIFINALE: 3,
  FINALE: 4,
  ROUNDROBIN: 10
} as const;

// Oggetto inverso per la traduzione nell'HTML
// (Puoi anche generarlo automaticamente)
export const RoundJudgedPanelTitles: Record<number, string> = {
  [RoundJudgedPanel.QUALIFICHE]: 'Qualifiche',
  [RoundJudgedPanel.SPAREGGIO]: 'Spareggio'
};

export const RoundMatchTitles: Record<number, string> = {
  [RoundMatch.OTTAVI]: 'Ottavi di finale',
  [RoundMatch.ROUNDROBIN]: 'Girone all italiana',
  [RoundMatch.QUARTI]: 'Quarti di Finale',
  [RoundMatch.SEMIFINALE]: 'Semifinale',
  [RoundMatch.FINALE]: 'Finale'
};

export const POINTS = {
  1: 1,
  2: 2,
  3: 3
};
