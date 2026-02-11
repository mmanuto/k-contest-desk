export interface CategoryProgress {
  category_name: string;
  category_id: string;
  specialty: string;
  current_phase: string;
  total: number;
  done: number;
  remaining: number;
  percent: number;
  is_closing: boolean;
  label: string;
}

export interface TatamiStatus {
  id: string;
  name: string;
  status: 'LIBERO' | 'OCCUPATO' | 'IN ATTESA';
  
  // Ora sono liste!
  active_categories: CategoryProgress[];
  queued_categories: CategoryProgress[];
}