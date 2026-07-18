export interface PatientOption {
  readonly id: number;
  readonly name: string;
  readonly phoneNumber: string;
  readonly identityCard: string;
  readonly dateOfBirth: string;
}

export interface DoctorOption {
  readonly id: number;
  readonly name: string;
  readonly speciality: string;
  readonly isActive: boolean;
}

export interface ProcedureOption {
  readonly id: number;
  readonly name: string;
  readonly price: number;
  readonly isActive: boolean;
}

export interface PaymentPreview {
  readonly totalPreview: number;
  readonly pendingPreview: number;
  readonly status: 'paid' | 'partial' | 'unpaid';
}

export const ATTENDANCE_FORM_VIEW_MODEL = {
  patients: [
    { id: 1, name: 'João Manuel Fernandes', phoneNumber: '+244 923 456 789', identityCard: '0067452LA041', dateOfBirth: '1985-06-14' },
    { id: 2, name: 'Ana Paula Domingos', phoneNumber: '+244 925 210 640', identityCard: '0048219LA037', dateOfBirth: '1992-10-03' },
    { id: 3, name: 'Mateus António Silva', phoneNumber: '+244 931 806 224', identityCard: '0089134LA049', dateOfBirth: '1978-02-21' },
  ] satisfies readonly PatientOption[],
  doctors: [
    { id: 1, name: 'António Santos', speciality: 'Clínica Geral', isActive: true },
    { id: 2, name: 'Maria Luísa', speciality: 'Pediatria', isActive: true },
    { id: 3, name: 'Ricardo Pereira', speciality: 'Cardiologia', isActive: true },
    { id: 4, name: 'Carlos Manuel', speciality: 'Ortopedia', isActive: false },
  ] satisfies readonly DoctorOption[],
  procedures: [
    { id: 1, name: 'Consulta geral', price: 15000, isActive: true },
    { id: 2, name: 'Hemograma completo', price: 4500, isActive: true },
    { id: 3, name: 'Glicémia', price: 2000, isActive: true },
    { id: 4, name: 'Procedimento inativo', price: 1000, isActive: false },
  ] satisfies readonly ProcedureOption[],
} as const;
