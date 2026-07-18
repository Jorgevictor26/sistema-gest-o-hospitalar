export interface DashboardPeriod {
  type: string;
  date_from: string;
  date_to: string;
  timezone: string;
}

export interface DashboardSummary {
  total_attendances: number;
  unique_patients: number;
  total_patients: number;
  total_charged: string;
  received_for_attendances: string;
  total_pending: string;
  cash_received_in_period: string;
}

export interface PaymentStatusSummary {
  paid: number;
  partial: number;
  unpaid: number;
}

export interface DashboardReport {
  period: DashboardPeriod;
  summary: DashboardSummary;
  payment_status: PaymentStatusSummary;
  by_doctor: unknown[];
  by_speciality: unknown[];
  by_procedure: unknown[];
  payments_by_method: unknown[];
}

export interface AdminDashboardResponse {
  scope: 'general';
  report: DashboardReport;
  appointments: {
    total: number;
    by_status: Record<string, number>;
    upcoming: unknown[];
  };
  procedures: Array<{
    id: number;
    procedure: string;
    total_performed: number;
    generated_value: string;
  }>;
  day_closure: {
    is_closed: boolean;
    id: number | null;
    closed_at: string | null;
    closed_by: { id: number; name: string } | null;
  };
  general: {
    active_doctors: number;
    total_patients: number;
    total_procedures: number;
    today_attendances: number;
  };
}

export interface RecentAttendance {
  id: number;
  attendance_date: string;
  patient: { id: number; name: string; phone_number: string };
  doctor: { id: number; name: string; speciality: string | null };
  procedures: Array<{ id: number; procedure: string; price: string }>;
  total_amount: string;
  amount_paid: string;
  pending_amount: string;
  payment_status: 'paid' | 'partial' | 'unpaid';
  registered_by: { id: number; name: string };
  created_at: string;
  updated_at: string;
}

export interface AttendanceCollectionResponse {
  data: RecentAttendance[];
  links: Record<string, string | null>;
  meta: Record<string, unknown>;
}

export interface AdminDashboardViewData {
  dashboard: AdminDashboardResponse;
  recentAttendances: RecentAttendance[];
}
