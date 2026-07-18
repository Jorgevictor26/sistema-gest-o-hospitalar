import { AdminDashboardResponse, RecentAttendance } from '../admin/admin-dashboard.models';

export interface DoctorOption {
  id: number;
  speciality: string | null;
  is_available: boolean;
  is_active: boolean;
  user: {
    id: number;
    name: string;
    email: string;
    phone_number: string | null;
  };
}

export interface PaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
}

export interface PaginatedAttendances {
  data: RecentAttendance[];
  links: Record<string, string | null>;
  meta: PaginationMeta;
}

export interface DailyClosure {
  id: number;
  date: string;
  is_closed: boolean;
  summary: unknown;
  closed_by: { id: number; name: string };
  closed_at: string;
  reopened_at: string | null;
  reopen_reason: string | null;
}

export interface DailyClosureStatus {
  date: string;
  is_closed: boolean;
  closure: DailyClosure | null;
}

export interface ReceptionistDashboardData {
  dashboard: AdminDashboardResponse;
  closureStatus: DailyClosureStatus;
  attendances: PaginatedAttendances;
  doctors: DoctorOption[];
}

export interface AttendanceFilters {
  search: string;
  doctorId: string;
  paymentStatus: '' | 'paid' | 'partial' | 'unpaid';
  page: number;
}
