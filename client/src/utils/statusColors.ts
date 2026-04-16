import { theme } from '../theme';

const map: Record<string, { bg: string; fg: string }> = {
  gray: { bg: theme.colors.graySoft, fg: theme.colors.gray },
  info: { bg: '#dbeafe', fg: '#2563eb' },
  warning: { bg: theme.colors.warningSoft, fg: theme.colors.warning },
  success: { bg: theme.colors.successSoft, fg: theme.colors.success },
  danger: { bg: theme.colors.dangerSoft, fg: theme.colors.danger },
};

export function statusColor(color: string): { bg: string; fg: string } {
  return map[color] ?? map.gray;
}
