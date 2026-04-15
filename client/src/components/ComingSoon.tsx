import { StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { theme } from '../theme';

export function ComingSoon({ title, icon }: { title: string; icon: keyof typeof Ionicons.glyphMap }) {
  return (
    <View style={styles.wrap}>
      <Ionicons name={icon} size={48} color={theme.colors.textSubtle} />
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.subtitle}>Coming in a future release.</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: theme.spacing.xl,
    backgroundColor: theme.colors.bg,
  },
  title: {
    marginTop: theme.spacing.md,
    fontSize: theme.font.sizes.xl,
    fontWeight: theme.font.weights.semibold,
    color: theme.colors.text,
  },
  subtitle: {
    marginTop: theme.spacing.xs,
    color: theme.colors.textMuted,
    fontSize: theme.font.sizes.md,
  },
});
