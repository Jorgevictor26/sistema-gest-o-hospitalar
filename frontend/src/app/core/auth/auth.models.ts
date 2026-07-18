export interface AuthUser {
  id: number;
  name: string;
  email: string;
  phone_number: string | null;
  is_active: boolean;
  roles: string[];
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface LoginResponse {
  user: AuthUser;
  token: string;
}
