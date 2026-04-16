import { apiRequest } from './client';

export type ProposalSummary = {
  id: number;
  uuid: string;
  project_title: string;
  client_name: string | null;
  client_company: string | null;
  status: string;
  status_label: string;
  status_color: string;
  total: number;
  proposal_date: string | null;
  valid_until: string | null;
  view_count: number;
  created_at: string | null;
};

export type ScopeItem = {
  id: number;
  category: string;
  title: string;
  description: string | null;
  bullets: string[];
  sort_order: number;
};

export type CostItem = {
  id: number;
  description: string;
  quantity: number;
  unit_price: number;
  amount: number;
  sort_order: number;
};

export type Milestone = {
  id: number;
  title: string;
  description: string | null;
  percentage: number;
  amount: number;
  due_description: string | null;
  payment_status: string | null;
  sort_order: number;
};

export type Term = {
  id: number;
  content: string;
  sort_order: number;
};

export type ProposalDetail = ProposalSummary & {
  client_id: number | null;
  client_email: string | null;
  client_domain: string | null;
  introduction: string | null;
  cost_notes: string | null;
  discount_enabled: boolean;
  discount_type: string | null;
  discount_value: number;
  subtotal: number;
  discount_amount: number;
  public_url: string | null;
  cover_image: string | null;
  overview_image: string | null;
  sent_at: string | null;
  accepted_at: string | null;
  scope_items: ScopeItem[];
  cost_items: CostItem[];
  milestones: Milestone[];
  terms: Term[];
};

export type ProposalCreateData = {
  client_id?: number;
  client_name?: string;
  client_email?: string;
  client_company?: string;
  project_title: string;
  proposal_date?: string;
  valid_until?: string;
  introduction?: string;
  cost_notes?: string;
  discount_enabled?: boolean;
  discount_type?: string;
  discount_value?: number;
};

export async function listProposals(): Promise<ProposalSummary[]> {
  const res = await apiRequest<{ data: ProposalSummary[] }>('/proposals');
  return res.data;
}

export async function getProposal(id: number): Promise<ProposalDetail> {
  const res = await apiRequest<{ data: ProposalDetail }>(`/proposals/${id}`);
  return res.data;
}

export async function createProposal(data: ProposalCreateData): Promise<ProposalDetail> {
  const res = await apiRequest<{ data: ProposalDetail }>('/proposals', { method: 'POST', body: data });
  return res.data;
}

export async function updateProposal(id: number, data: Partial<ProposalCreateData>): Promise<ProposalDetail> {
  const res = await apiRequest<{ data: ProposalDetail }>(`/proposals/${id}`, { method: 'PUT', body: data });
  return res.data;
}

export async function sendProposal(id: number): Promise<void> {
  await apiRequest(`/proposals/${id}/send`, { method: 'POST' });
}

export async function deleteProposal(id: number): Promise<void> {
  await apiRequest(`/proposals/${id}`, { method: 'DELETE' });
}

// Scope Items
export async function addScopeItem(proposalId: number, data: { category: string; title: string; description?: string; bullets?: string[] }): Promise<ScopeItem> {
  const res = await apiRequest<{ data: ScopeItem }>(`/proposals/${proposalId}/scope-items`, { method: 'POST', body: data });
  return res.data;
}

export async function updateScopeItem(proposalId: number, itemId: number, data: Partial<ScopeItem>): Promise<ScopeItem> {
  const res = await apiRequest<{ data: ScopeItem }>(`/proposals/${proposalId}/scope-items/${itemId}`, { method: 'PUT', body: data });
  return res.data;
}

export async function deleteScopeItem(proposalId: number, itemId: number): Promise<void> {
  await apiRequest(`/proposals/${proposalId}/scope-items/${itemId}`, { method: 'DELETE' });
}

// Cost Items
export async function addCostItem(proposalId: number, data: { description: string; quantity: number; unit_price: number }): Promise<CostItem> {
  const res = await apiRequest<{ data: CostItem }>(`/proposals/${proposalId}/cost-items`, { method: 'POST', body: data });
  return res.data;
}

export async function updateCostItem(proposalId: number, itemId: number, data: Partial<CostItem>): Promise<CostItem> {
  const res = await apiRequest<{ data: CostItem }>(`/proposals/${proposalId}/cost-items/${itemId}`, { method: 'PUT', body: data });
  return res.data;
}

export async function deleteCostItem(proposalId: number, itemId: number): Promise<void> {
  await apiRequest(`/proposals/${proposalId}/cost-items/${itemId}`, { method: 'DELETE' });
}

// Milestones
export async function addMilestone(proposalId: number, data: { title: string; description?: string; percentage?: number; amount?: number; due_description?: string }): Promise<Milestone> {
  const res = await apiRequest<{ data: Milestone }>(`/proposals/${proposalId}/milestones`, { method: 'POST', body: data });
  return res.data;
}

export async function updateMilestone(proposalId: number, itemId: number, data: Partial<Milestone>): Promise<Milestone> {
  const res = await apiRequest<{ data: Milestone }>(`/proposals/${proposalId}/milestones/${itemId}`, { method: 'PUT', body: data });
  return res.data;
}

export async function deleteMilestone(proposalId: number, itemId: number): Promise<void> {
  await apiRequest(`/proposals/${proposalId}/milestones/${itemId}`, { method: 'DELETE' });
}
