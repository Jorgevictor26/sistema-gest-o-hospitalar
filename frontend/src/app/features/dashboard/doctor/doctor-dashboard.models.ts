export type DoctorDashboardStatus = 'loading' | 'ready' | 'error';
export type AppointmentStatus = 'scheduled' | 'confirmed' | 'completed' | 'no_show' | 'cancelled';

export interface DoctorMetric {
  attendances_count: number;
  patients_count: number;
  generated_value: string;
  commission_amount: string;
}

export interface DoctorDashboardResponse {
  scope: 'doctor';
  doctor: {
    id: number;
    name: string;
    speciality: string | null;
    professional_number: string | null;
    commission_percentage: string;
  };
  statistics: { today: DoctorMetric; week: DoctorMetric; month: DoctorMetric };
  recent_attendances: Array<{
    id: number;
    attendance_date: string;
    patient: { id: number; name: string };
    total_amount: string;
    commission_percentage: string;
    commission_amount: string;
  }>;
  daily_patient_flow: Array<{ attendance_date: string; total: number }>;
  top_procedures_this_month: Array<{ id: number; procedure: string; total_performed: number }>;
}

export interface DoctorProfileResponse {
  data: {
    id: number;
    name: string;
    email: string;
    phone_number: string | null;
    speciality: string | null;
    professional_number: string | null;
    is_available: boolean;
    commission_percentage: string;
  };
}

export interface DoctorAttendance {
  id: number;
  attendance_date: string;
  patient: { id: number; name: string; phone_number: string | null };
  doctor: { id: number; name: string; speciality: string | null };
  procedures: Array<{ id: number; procedure: string; price: string }>;
  total_amount: string;
  amount_paid: string;
  pending_amount: string;
  payment_status: 'paid' | 'partial' | 'unpaid';
  commission_percentage?: string;
  commission_amount?: string;
  registered_by: { id: number; name: string };
  created_at: string;
  updated_at: string;
}

export interface AttendanceCollectionResponse {
  data: DoctorAttendance[];
  links: Record<string, string | null>;
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface DoctorCommissionResponse {
  period: { type: 'daily' | 'weekly' | 'monthly' | 'custom'; date_from: string; date_to: string };
  summary: { total_attendances: number; total_generated: string; total_commission: string };
  attendances: {
    data: Array<{
      attendance_id: number;
      attendance_date: string;
      patient: { id: number; name: string };
      total_amount: string;
      commission_percentage: string;
      commission_amount: string;
    }>;
    links: Record<string, string | null>;
    meta: Record<string, unknown>;
  };
}

export interface DoctorStatisticCard {
  readonly label: string;
  readonly value: number | null;
  readonly icon: string;
  readonly tone: string;
}

export interface PatientFlowPoint { readonly label: string; readonly value: number; readonly percentage: number }
export interface AgeDistributionItem { readonly label: string; readonly value: number; readonly percentage: number }

export interface TodayPatient {
  readonly attendanceId: number;
  readonly patient: string;
  readonly age: number | null;
  readonly time: string | null;
  readonly procedures: readonly string[];
  readonly status: AppointmentStatus | null;
}

export interface DoctorAppointment {
  readonly id: number;
  readonly time: string;
  readonly patient: string;
  readonly status: AppointmentStatus;
  readonly durationMinutes: number;
}

export interface FrequentProcedure { readonly id: number; readonly name: string; readonly count: number; readonly percentage: number }
export interface CommissionSummary { readonly today: number | null; readonly week: number | null; readonly month: number | null }

export interface DoctorDashboardViewModel {
  readonly speciality: string | null;
  readonly statistics: readonly DoctorStatisticCard[];
  readonly patientFlow: readonly PatientFlowPoint[];
  readonly ageDistribution: readonly AgeDistributionItem[];
  readonly todayPatients: readonly TodayPatient[];
  readonly appointments: readonly DoctorAppointment[];
  readonly frequentProcedures: readonly FrequentProcedure[];
  readonly commissions: CommissionSummary;
}
