import { StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Button } from '../../src/components/Button';
import { useAuth } from '../../src/auth/AuthContext';
import { theme } from '../../src/theme';

export default function Settings() {
  const { user, signOut } = useAuth();

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <View style={styles.header}>
        <Text style={styles.title}>Settings</Text>
        <Text style={styles.subtitle}>Account & preferences</Text>
      </View>
      <View style={styles.container}>
        <View style={styles.card}>
          <Text style={styles.label}>Signed in as</Text>
          <Text style={styles.name}>{user?.name}</Text>
          <Text style={styles.email}>{user?.email}</Text>
          {user?.role ? <Text style={styles.role}>{user.role}</Text> : <Text style={styles.role}>Administrator</Text>}
        </View>

        <Button label="Sign Out" onPress={signOut} variant="secondary" />
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: theme.colors.bg },
  header: {
    paddingHorizontal: theme.spacing.lg,
    paddingTop: theme.spacing.lg,
    paddingBottom: theme.spacing.md,
  },
  title: {
    fontSize: theme.font.sizes.xxl,
    fontWeight: theme.font.weights.bold,
    color: theme.colors.text,
  },
  subtitle: { color: theme.colors.textMuted, fontSize: theme.font.sizes.sm, marginTop: 2 },
  container: { padding: theme.spacing.lg, gap: theme.spacing.lg },
  card: {
    backgroundColor: theme.colors.surface,
    borderRadius: theme.radius.lg,
    borderWidth: 1,
    borderColor: theme.colors.border,
    padding: theme.spacing.lg,
  },
  label: {
    fontSize: theme.font.sizes.xs,
    color: theme.colors.textMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: theme.spacing.xs,
  },
  name: {
    fontSize: theme.font.sizes.xl,
    fontWeight: theme.font.weights.semibold,
    color: theme.colors.text,
  },
  email: {
    fontSize: theme.font.sizes.md,
    color: theme.colors.textMuted,
    marginTop: 2,
  },
  role: {
    marginTop: theme.spacing.sm,
    color: theme.colors.primary,
    fontSize: theme.font.sizes.sm,
    fontWeight: theme.font.weights.medium,
  },
});
