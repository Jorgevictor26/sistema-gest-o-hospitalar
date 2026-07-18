import {
  DailyClosureResponse,
  DailyClosureStatusResponse,
  PaginatedAttendancesApiResponse,
} from './daily-closing.api-models';
import {
  ClosingAuditEntry,
  DailyClosingViewModel,
  PaymentMethodSummary,
  createDailyClosingViewModel,
} from './daily-closing.viewmodel';

export function mapDailyClosingToViewModel(
  response: DailyClosureStatusResponse,
  isAdmin: boolean,
): DailyClosingViewModel {
  if (!response?.date) throw new Error('INVALID_RESPONSE');

  const empty = createDailyClosingViewModel(response.date, isAdmin);
  const closure = response.closure;
  if (!response.is_closed) {
    return {
      ...empty,
      state: 'open',
      closure: closure
        ? {
            id: String(closure.id),
            closedAt: closure.closed_at,
            closedBy: closure.closed_by.name,
          }
        : empty.closure,
      audit: closure ? auditEntries(closure) : undefined,
      errors: {
        ...empty.errors,
        summary:
          'O endpoint não devolve os totais atuais de dias abertos. Os valores permanecem indisponíveis para evitar apresentar um snapshot antigo como provisório.',
      },
    };
  }

  if (!closure) throw new Error('INVALID_RESPONSE');

  const report = closure.summary;
  return {
    ...empty,
    state: response.is_closed ? 'closed' : 'open',
    closure: {
      id: String(closure.id),
      closedAt: closure.closed_at,
      closedBy: closure.closed_by.name,
    },
    summary: {
      totalPatients: report.summary.total_patients,
      totalAttendances: report.summary.total_attendances,
      totalBilled: money(report.summary.total_charged),
      totalReceived: money(report.summary.received_for_attendances),
      totalPending: money(report.summary.total_pending),
    },
    paymentStatuses: [
      { status: 'paid', total: report.payment_status.paid },
      { status: 'partial', total: report.payment_status.partial },
      { status: 'unpaid', total: report.payment_status.unpaid },
    ],
    paymentMethods: report.payments_by_method.map((payment) => ({
      method: paymentMethod(payment.method),
      amount: money(payment.total_received),
    })),
    doctors: report.by_doctor.map((doctor) => ({
      id: doctor.doctor_id,
      doctor: doctor.doctor,
      speciality: doctor.speciality ?? 'Não informada',
      patients: null,
      attendances: doctor.total_attendances,
      totalBilled: money(doctor.total_charged),
      totalReceived: money(doctor.total_received),
      totalPending: money(doctor.total_pending),
    })),
    procedures: report.by_procedure.map((procedure) => ({
      id: procedure.procedure_id,
      procedure: procedure.procedure,
      quantity: procedure.usage_count,
      appliedPrice: null,
      totalGenerated: money(procedure.total_charged),
    })),
    attendances: [],
    audit: auditEntries(closure),
    permissions: {
      ...empty.permissions,
      canClose: !response.is_closed,
      canReopen: response.is_closed && isAdmin,
    },
    errors: {
      ...empty.errors,
      attendances: null,
      doctors: 'O endpoint não fornece a quantidade de pacientes distintos por médico.',
      procedures:
        'O endpoint não fornece preço aplicado nem preço médio. O total apresentado vem diretamente da soma histórica de attendance_procedure.price.',
    },
  };
}

function auditEntries(closure: DailyClosureResponse): ClosingAuditEntry[] {
  const entries: ClosingAuditEntry[] = [
    {
      id: closure.id,
      action: 'Fecho diário',
      user: closure.closed_by.name,
      dateTime: closure.closed_at,
      reason: null,
    },
  ];

  if (closure.reopened_at && closure.reopened_by) {
    entries.push({
      id: -closure.id,
      action: 'Reabertura do dia',
      user: closure.reopened_by.name,
      dateTime: closure.reopened_at,
      reason: closure.reopen_reason,
    });
  }

  return entries;
}

function paymentMethod(method: string): PaymentMethodSummary['method'] {
  return (
    {
      cash: 'Numerário',
      card: 'TPA',
      bank_transfer: 'Transferência',
      insurance: 'Seguro',
      other: 'Outro',
    }[method] ?? method
  );
}

function money(value: string): number {
  const parsed = Number(value);
  if (!Number.isFinite(parsed)) throw new Error('INVALID_RESPONSE');
  return parsed;
}

export function mapAttendancesToViewModel(response: PaginatedAttendancesApiResponse) {
  if (!response?.data || !response.meta) throw new Error('INVALID_RESPONSE');

  return {
    attendances: response.data.map((attendance) => ({
      id: attendance.id,
      code: `#${attendance.id}`,
      patient: attendance.patient.name,
      patientPhone: attendance.patient.phone_number,
      doctor: attendance.doctor.name,
      doctorSpeciality: attendance.doctor.speciality,
      procedures: attendance.procedures.map((procedure) => procedure.procedure),
      procedureDetails: attendance.procedures.map((procedure) => ({
        name: procedure.procedure,
        price: money(procedure.price),
      })),
      total: money(attendance.total_amount),
      amountPaid: money(attendance.amount_paid),
      pendingAmount: money(attendance.pending_amount),
      paymentStatus: attendance.payment_status,
      time: attendance.created_at,
      attendanceDate: attendance.attendance_date,
      registeredAt: attendance.created_at,
    })),
    pagination: {
      currentPage: response.meta.current_page,
      lastPage: response.meta.last_page,
      perPage: response.meta.per_page,
      total: response.meta.total,
    },
  };
}
