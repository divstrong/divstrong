import { StyleSheet, Text, View } from 'react-native';
import { scoreColors, theme, type ScoreColor } from '../theme';

type Props = {
  score: number | null;
  label: string;
  color: ScoreColor;
};

export function ScoreBadge({ score, label, color }: Props) {
  const c = scoreColors[color];
  return (
    <View style={[styles.badge, { backgroundColor: c.bg }]}>
      <Text style={[styles.text, { color: c.fg }]}>
        {score !== null ? `${score}/100 · ${label}` : 'Pending'}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: theme.spacing.sm,
    paddingVertical: 4,
    borderRadius: 999,
    alignSelf: 'flex-start',
  },
  text: {
    fontSize: theme.font.sizes.xs,
    fontWeight: theme.font.weights.semibold,
  },
});
