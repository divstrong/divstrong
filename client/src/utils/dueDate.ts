import { theme } from '../theme';

export type DueState = {
  label: string;
  sub: string | null;
  color: string;
};

export function dueDateState(isoDate: string | null): DueState {
  if (!isoDate) {
    return { label: '—', sub: null, color: theme.colors.textSubtle };
  }

  const due = new Date(isoDate + 'T00:00:00');
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const msPerDay = 1000 * 60 * 60 * 24;
  const diff = Math.round((due.getTime() - today.getTime()) / msPerDay);

  const label = due.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });

  let color = theme.colors.text;
  let sub: string | null = null;

  if (diff < 0) {
    color = theme.colors.danger;
    sub = `${Math.abs(diff)} days overdue`;
  } else if (diff === 0) {
    color = theme.colors.warning;
    sub = 'Due today';
  } else if (diff <= 7) {
    color = theme.colors.warning;
    sub = `in ${diff} days`;
  } else {
    sub = `in ${diff} days`;
  }

  return { label, sub, color };
}
