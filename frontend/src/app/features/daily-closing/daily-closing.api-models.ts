export interface DailyClosureReportResponse {
  period: { type: string; date_from: string; date_to: string; timezone: string };
  summary: {
    total_attendances: number;
    unique_patients: number;
    total_patients: number;
    total_charged: string;
    received_for_attendances: string;
    total_pending: string;
    cash_received_in_period: string;
  };
  payment_status: { paid: number; partial: number; unpaid: number };
  by_doctor: Array<{
    doctor_id: number;
    doctor: string;
    speciality: string | null;
    total_attendances: number;
    total_charged: string;
    total_received: string;
    total_pending: string;
  }>;
  by_speciality: Array<Record<string, unknown>>;
  by_procedure: Array<{
    procedure_id: number;
    procedure: string;
    usage_count: number;
    total_charged: string;
  }>;
  payments_by_method: Array<{
    method: 'cash' | 'bank_transfer' | 'card' | 'insurance' | 'other';
    payments_count: number;
    total_received: string;
  }>;
}

export interface DailyClosureResponse {
  id: number;
  date: string;
  is_closed: boolean;
  summary: DailyClosureReportResponse;
  closed_by: { id: number; name: string };
  closed_at: string;
  reopened_by?: { id: number; name: string };
  reopened_at: string | null;
  reopen_reason: string | null;
}

export interface DailyClosureStatusResponse {
  date: string;
  is_closed: boolean;
  closure: DailyClosureResponse | null;
}

export interface AttendanceApiResponse {
  id: number;
  attendance_date: string;
  patient: { id: number; name: string; phone_number: string };
  doctor: { id: number; name: string; speciality: string | null };
  procedures: Array<{ id: number; procedure: string; price: string }>;
  total_amount: string;
  amount_paid: string;
  pending_amount: string;
  payment_status: 'paid' | 'partial' | 'unpaid';
  created_at: string;
  updated_at: string;
}

export interface PaginatedAttendancesApiResponse {
  data: AttendanceApiResponse[];
  links: { first: string | null; last: string | null; prev: string | null; next: string | null };
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
