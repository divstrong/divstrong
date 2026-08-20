import { apiRequest } from './client';

export type ScoreColor = 'success' | 'warning' | 'info' | 'danger' | 'gray';

export type RfpScreenSummary = {
  id: number;
  rfp_name: string | null;
  status: string;
  score: number | null;
  score_label: string;
  score_color: ScoreColor;
  due_date: string | null;
  created_at: string | null;
  analyzed_at: string | null;
};

export type RfpScreenDetail = RfpScreenSummary & {
  summary: string | null;
  red_flags: string[];
  requirements: string[];
  contact: {
    name: string | null;
    title: string | null;
    department: string | null;
    email: string | null;
    phone: string | null;
  };
  file: {
    original_filename: string | null;
    file_type: string | null;
    url: string | null;
  };
};

export async function listScreens(): Promise<RfpScreenSummary[]> {
  const res = await apiRequest<{ data: RfpScreenSummary[] }>('/rfp-screens');
  return res.data;
}

export async function getScreen(id: number): Promise<RfpScreenDetail> {
  const res = await apiRequest<{ data: RfpScreenDetail }>(`/rfp-screens/${id}`);
  return res.data;
}

export type FilePick = { uri: string; name: string; mimeType?: string };

export async function createScreen(file: FilePick, rfpName?: string, prompt?: string): Promise<RfpScreenDetail> {
  const form = new FormData();
  form.append('file', {
    uri: file.uri,
    name: file.name,
    type: file.mimeType ?? 'application/octet-stream',
  } as any);
  if (rfpName) form.append('rfp_name', rfpName);
  if (prompt) form.append('prompt', prompt);

  const res = await apiRequest<{ data: RfpScreenDetail }>('/rfp-screens', {
    method: 'POST',
    formData: form,
  });
  return res.data;
}

export async function rescanScreen(id: number, file?: FilePick): Promise<RfpScreenDetail> {
  const form = new FormData();
  if (file) {
    form.append('file', {
      uri: file.uri,
      name: file.name,
      type: file.mimeType ?? 'application/octet-stream',
    } as any);
  }
  const res = await apiRequest<{ data: RfpScreenDetail }>(`/rfp-screens/${id}/rescan`, {
    method: 'POST',
    formData: form,
  });
  return res.data;
}

export type EngagementUnit = 'sprint' | 'day' | 'hour';

export type ProposalEngagement = {
  /** Billing unit. Defaults server-side to 'sprint'. */
  unit?: EngagementUnit;
  /** How many of that unit. Defaults server-side per unit. */
  quantity?: number;
  /** Optional extra guidance for how the scope should be drafted. */
  scope_prompt?: string;
};

export async function createProposalFromScreen(
  id: number,
  engagement?: ProposalEngagement,
): Promise<{
  proposal_id: number;
  unit?: EngagementUnit;
  quantity?: number;
  total?: number;
  message: string;
}> {
  // Drafting reads the source RFP document and writes the full delivery plan,
  // so this runs longer than the old summary-only generation.
  return apiRequest(`/rfp-screens/${id}/create-proposal`, {
    method: 'POST',
    body: engagement,
    timeoutMs: 300000,
  });
}

export async function deleteScreen(id: number): Promise<void> {
  await apiRequest(`/rfp-screens/${id}`, { method: 'DELETE' });
}
