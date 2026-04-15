import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'divstrong_api_token';

const baseUrl: string =
  (Constants.expoConfig?.extra?.apiBaseUrl as string | undefined) ??
  'http://127.0.0.1:8000/api';

export class ApiError extends Error {
  constructor(public status: number, message: string, public body?: any) {
    super(message);
  }
}

export async function getToken(): Promise<string | null> {
  return SecureStore.getItemAsync(TOKEN_KEY);
}

export async function setToken(token: string | null): Promise<void> {
  if (token) await SecureStore.setItemAsync(TOKEN_KEY, token);
  else await SecureStore.deleteItemAsync(TOKEN_KEY);
}

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
  body?: any;
  formData?: FormData;
  signal?: AbortSignal;
  timeoutMs?: number;
};

export async function apiRequest<T = any>(
  path: string,
  { method = 'GET', body, formData, signal, timeoutMs = 15000 }: RequestOptions = {},
): Promise<T> {
  const token = await getToken();
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };
  if (token) headers.Authorization = `Bearer ${token}`;

  let payload: BodyInit | undefined;
  if (formData) {
    payload = formData as unknown as BodyInit;
  } else if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  const controller = new AbortController();
  const onAbort = () => controller.abort();
  if (signal) signal.addEventListener('abort', onAbort);
  const timer = setTimeout(
    () => controller.abort(new Error(`Request timed out after ${timeoutMs}ms`)),
    timeoutMs,
  );

  let res: Response;
  try {
    res = await fetch(`${baseUrl}${path}`, { method, headers, body: payload, signal: controller.signal });
  } catch (e: any) {
    if (controller.signal.aborted) {
      throw new ApiError(0, `Cannot reach server at ${baseUrl}. Check the server is running and the URL is correct.`);
    }
    throw new ApiError(0, e?.message ?? 'Network error');
  } finally {
    clearTimeout(timer);
    if (signal) signal.removeEventListener('abort', onAbort);
  }

  const text = await res.text();
  const parsed = text ? safeParse(text) : null;

  if (!res.ok) {
    const msg = (parsed && (parsed.message || parsed.error)) || `Request failed (${res.status})`;
    throw new ApiError(res.status, msg, parsed);
  }
  return parsed as T;
}

function safeParse(text: string): any {
  try { return JSON.parse(text); } catch { return text; }
}
