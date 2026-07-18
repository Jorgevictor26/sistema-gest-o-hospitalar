export type DailyClosingState = 'open' | 'closed';

export interface PaymentMethodSummary {
  readonly method: string;
  readonly amount: number;
}

export interface PaymentStatusSummary {
  readonly status: 'paid' | 'partial' | 'unpaid';
  readonly total: number;
}

export interface DailyClosingSummary {
  readonly totalPatients: number | null;
  readonly totalAttendances: number | null;
  readonly totalBilled: number | null;
  readonly totalReceived: number | null;
  readonly totalPending: number | null;
}

export interface ClosingAttendance {
  readonly id: number;
  readonly code: string;
  readonly patient: string;
  readonly patientPhone: string;
  readonly doctor: string;
  readonly doctorSpeciality: string | null;
  readonly procedures: readonly string[];
  readonly procedureDetails: readonly { name: string; price: number }[];
  readonly total: number;
  readonly amountPaid: number;
  readonly pendingAmount: number;
  readonly paymentStatus: 'paid' | 'partial' | 'unpaid';
  readonly time: string;
  readonly attendanceDate: string;
  readonly registeredAt: string;
}

export interface ClosingAuditEntry {
  readonly id: number;
  readonly action: string;
  readonly user: string;
  readonly dateTime: string;
  readonly reason: string | null;
}

export interface DoctorBreakdown {
  readonly id: number;
  readonly doctor: string;
  readonly speciality: string;
  readonly patients: number | null;
  readonly attendances: number;
  readonly totalBilled: number;
  readonly totalReceived: number;
  readonly totalPending: number;
}

export interface ProcedureBreakdown {
  readonly id: number;
  readonly procedure: string;
  readonly quantity: number;
  readonly appliedPrice: number | null;
  readonly totalGenerated: number;
}

export interface DailyClosingViewModel {
  readonly state: DailyClosingState;
  readonly selectedDate: string;
  readonly closure: {
    readonly id: string | null;
    readonly closedAt: string | null;
    readonly closedBy: string | null;
  };
  readonly summary: DailyClosingSummary;
  readonly paymentStatuses: readonly PaymentStatusSummary[];
  readonly paymentMethods: readonly PaymentMethodSummary[];
  readonly attendances: readonly ClosingAttendance[];
  readonly attendancePagination: {
    readonly currentPage: number;
    readonly lastPage: number;
    readonly perPage: number;
    readonly total: number;
  };
  readonly doctors: readonly DoctorBreakdown[];
  readonly procedures: readonly ProcedureBreakdown[];
  readonly audit?: readonly ClosingAuditEntry[];
  readonly permissions: {
    readonly canClose: boolean;
    readonly canReopen: boolean;
    readonly pdfEnabled: boolean;
    readonly csvEnabled: boolean;
    readonly printEnabled: boolean;
  };
  readonly loading: {
    readonly page: boolean;
    readonly summary: boolean;
    readonly attendances: boolean;
    readonly doctors: boolean;
    readonly procedures: boolean;
  };
  readonly errors: {
    readonly page: string | null;
    readonly summary: string | null;
    readonly attendances: string | null;
    readonly doctors: string | null;
    readonly procedures: string | null;
  };
}

export function createDailyClosingViewModel(
  selectedDate: string,
  isAdmin: boolean,
  loading = false,
): DailyClosingViewModel {
  return {
    state: 'open',
    selectedDate,
    closure: { id: null, closedAt: null, closedBy: null },
    summary: {
      totalPatients: null,
      totalAttendances: null,
      totalBilled: null,
      totalReceived: null,
      totalPending: null,
    },
    paymentStatuses: [],
    paymentMethods: [],
    attendances: [],
    attendancePagination: { currentPage: 1, lastPage: 1, perPage: 15, total: 0 },
    doctors: [],
    procedures: [],
    audit: undefined,
    permissions: {
      canClose: true,
      canReopen: isAdmin,
      pdfEnabled: false,
      csvEnabled: false,
      printEnabled: false,
    },
    loading: {
      page: loading,
      summary: loading,
      attendances: loading,
      doctors: loading,
      procedures: loading,
    },
    errors: { page: null, summary: null, attendances: null, doctors: null, procedures: null },
  };
}
