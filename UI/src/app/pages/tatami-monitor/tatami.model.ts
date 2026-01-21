export interface TatamiStatus {
  id: string;   // Es: "T1"
  name: string; // Es: "Tatami 1" <--- Nuovo campo
  status: 'LIBERO' | 'OCCUPATO';
  
  // Opzionali perché se è libero potrebbero essere vuoti o null
  categoryName?: string; 
  specialty?: string;
  currentPhase?: string;

  total: number;
  done: number;
  remaining: number;
  percent: number;
  isClosing: boolean;
  label?: string; // Es: "Incontri" o "Atleti"
}