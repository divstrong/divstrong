import { apiRequest, setToken } from './client';

export type AuthUser = {
  id: number;
  name: string;
  email: string;
  role: string | null;
  is_admin: boolean;
};

export async function login(email: string, password: string): Promise<AuthUser> {
  const res = await apiRequest<{ token: string; user: AuthUser }>('/auth/login', {
    method: 'POST',
    body: { email, password, device_name: 'divstrong-mobile' },
  });
  await setToken(res.token);
  return res.user;
}

export async function logout(): Promise<void> {
  try { await apiRequest('/auth/logout', { method: 'POST' }); } catch {}
  await setToken(null);
}

export async function me(): Promise<AuthUser> {
  const res = await apiRequest<{ user: AuthUser }>('/auth/me');
  return res.user;
}

export async function forgotPassword(email: string): Promise<string> {
  const res = await apiRequest<{ message: string }>('/auth/forgot-password', {
    method: 'POST',
    body: { email },
  });
  return res.message;
}
