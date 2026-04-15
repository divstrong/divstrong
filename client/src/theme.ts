export const theme = {
  colors: {
    primary: '#ed2537',
    primaryDark: '#c81e2d',
    primarySoft: '#fde7e9',
    bg: '#ffffff',
    surface: '#ffffff',
    surfaceAlt: '#fafafa',
    border: '#e4e4e7',
    borderStrong: '#d4d4d8',
    text: '#18181b',
    textMuted: '#71717a',
    textSubtle: '#a1a1aa',
    success: '#16a34a',
    successSoft: '#dcfce7',
    warning: '#d97706',
    warningSoft: '#fef3c7',
    danger: '#dc2626',
    dangerSoft: '#fee2e2',
    gray: '#71717a',
    graySoft: '#f4f4f5',
  },
  radius: { sm: 6, md: 10, lg: 14, xl: 20 },
  spacing: { xs: 4, sm: 8, md: 12, lg: 16, xl: 24, xxl: 32 },
  font: {
    sizes: { xs: 12, sm: 13, md: 15, lg: 17, xl: 20, xxl: 26 },
    weights: { regular: '400', medium: '500', semibold: '600', bold: '700' } as const,
  },
};

export type ScoreColor = 'success' | 'warning' | 'danger' | 'gray';

export const scoreColors: Record<ScoreColor, { bg: string; fg: string }> = {
  success: { bg: theme.colors.successSoft, fg: theme.colors.success },
  warning: { bg: theme.colors.warningSoft, fg: theme.colors.warning },
  danger: { bg: theme.colors.dangerSoft, fg: theme.colors.danger },
  gray: { bg: theme.colors.graySoft, fg: theme.colors.gray },
};
