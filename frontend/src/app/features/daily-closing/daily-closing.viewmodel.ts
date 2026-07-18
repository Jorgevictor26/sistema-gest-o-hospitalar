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
  readonly totalPatients: number;
  readonly totalAttendances: number;
  readonly totalBilled: number;
  readonly totalReceived: number;
  readonly totalPending: number;
}

export interface ClosingAttendance {
  readonly id: number;
  readonly code: string;
  readonly patient: string;
  readonly doctor: string;
  readonly procedures: readonly string[];
  readonly total: number;
  readonly amountPaid: number;
  readonly pendingAmount: number;
  readonly paymentStatus: 'paid' | 'partial' | 'unpaid';
  readonly time: string;
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
  readonly patients: number;
  readonly attendances: number;
  readonly totalBilled: number;
  readonly totalReceived: number;
  readonly totalPending: number;
}

export interface ProcedureBreakdown {
  readonly id: number;
  readonly procedure: string;
  readonly quantity: number;
  readonly appliedPrice: number;
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

export function createDailyClosingViewModel(selectedDate: string, isAdmin: boolean): DailyClosingViewModel {
  return {
  state: 'open',
  selectedDate,
  closure: { id: null, closedAt: null, closedBy: null },
  summary: {
    totalPatients: 0,
    totalAttendances: 0,
    totalBilled: 0,
    totalReceived: 0,
    totalPending: 0,
  },
  paymentStatuses: [],
  paymentMethods: [],
  attendances: [],
  doctors: [],
  procedures: [],
  audit: undefined,
  permissions: {
    canClose: true,
    canReopen: isAdmin,
    pdfEnabled: true,
    csvEnabled: true,
    printEnabled: true,
  },
  loading: { page: false, summary: false, attendances: false, doctors: false, procedures: false },
  errors: { page: null, summary: null, attendances: null, doctors: null, procedures: null },
  };
}
