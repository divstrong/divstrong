import { apiRequest } from './client';

export type ClientSummary = {
  id: number;
  name: string;
  email: string;
  company: string | null;
  phone: string | null;
  proposals_count: number;
};

export type ClientDetail = ClientSummary & {
  title: string | null;
  domain: string | null;
  address1: string | null;
  address2: string | null;
  city: string | null;
  state: string | null;
  zip: string | null;
  notes: string | null;
  created_at: string | null;
};

export type ClientFormData = {
  name: string;
  title?: string;
  email: string;
  company?: string;
  phone?: string;
  domain?: string;
  address1?: string;
  address2?: string;
  city?: string;
  state?: string;
  zip?: string;
  notes?: string;
};

export async function listClients(search?: string): Promise<ClientSummary[]> {
  const q = search ? `?search=${encodeURIComponent(search)}` : '';
  const res = await apiRequest<{ data: ClientSummary[] }>(`/clients${q}`);
  return res.data;
}

export async function getClient(id: number): Promise<ClientDetail> {
  const res = await apiRequest<{ data: ClientDetail }>(`/clients/${id}`);
  return res.data;
}

export async function createClient(data: ClientFormData): Promise<ClientDetail> {
  const res = await apiRequest<{ data: ClientDetail }>('/clients', { method: 'POST', body: data });
  return res.data;
}

export async function updateClient(id: number, data: ClientFormData): Promise<ClientDetail> {
  const res = await apiRequest<{ data: ClientDetail }>(`/clients/${id}`, { method: 'PUT', body: data });
  return res.data;
}

export async function deleteClient(id: number): Promise<void> {
  await apiRequest(`/clients/${id}`, { method: 'DELETE' });
}
