import * as DocumentPicker from 'expo-document-picker';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Linking, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { Button } from '../../../src/components/Button';
import { ScoreBadge } from '../../../src/components/ScoreBadge';
import { deleteScreen, getScreen, rescanScreen, type FilePick, type RfpScreenDetail } from '../../../src/api/screens';
import { theme } from '../../../src/theme';
import { dueDateState } from '../../../src/utils/dueDate';

const ACCEPT = [
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'text/plain',
  'text/csv',
  'text/markdown',
];

export default function ScreenDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const [screen, setScreen] = useState<RfpScreenDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [rescanning, setRescanning] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setScreen(await getScreen(Number(id)));
    } catch (e: any) {
      setError(e?.message ?? 'Failed to load RFP.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => { load(); }, [load]);

  async function handleRescan(replace: boolean) {
    let file: FilePick | undefined;
    if (replace) {
      const res = await DocumentPicker.getDocumentAsync({ type: ACCEPT, multiple: false, copyToCacheDirectory: true });
      if (res.canceled || !res.assets?.[0]) return;
      const a = res.assets[0];
      file = { uri: a.uri, name: a.name, mimeType: a.mimeType ?? undefined };
    }
    setRescanning(true);
    try {
      setScreen(await rescanScreen(Number(id), file));
    } catch (e: any) {
      Alert.alert('Rescan failed', e?.message ?? 'Please try again.');
    } finally {
      setRescanning(false);
    }
  }

  function confirmDelete() {
    Alert.alert('Delete RFP?', 'This cannot be undone.', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: async () => {
          try {
            await deleteScreen(Number(id));
            router.back();
          } catch (e: any) {
            Alert.alert('Delete failed', e?.message ?? 'Please try again.');
          }
        },
      },
    ]);
  }

  if (loading) {
    return (
      <SafeAreaView style={[styles.safe, styles.centered]}>
        <ActivityIndicator color={theme.colors.primary} />
      </SafeAreaView>
    );
  }

  if (error || !screen) {
    return (
      <SafeAreaView style={[styles.safe, styles.centered]}>
        <Text style={styles.error}>{error ?? 'Not found.'}</Text>
      </SafeAreaView>
    );
  }

  const due = dueDateState(screen.due_date);

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.title}>{screen.rfp_name || 'Untitled RFP'}</Text>

        <View style={styles.badgeRow}>
          <ScoreBadge score={screen.score} label={screen.score_label} color={screen.score_color} />
          {screen.status !== 'completed' && (
            <View style={styles.statusPill}>
              <Text style={styles.statusText}>{screen.status}</Text>
            </View>
          )}
        </View>

        <View style={styles.dueRow}>
          <Ionicons name="calendar-outline" size={16} color={due.color} />
          <Text style={[styles.dueText, { color: due.color }]}>
            {due.label}{due.sub ? ` · ${due.sub}` : ''}
          </Text>
        </View>

        {screen.summary ? (
          <Section title="Summary">
            <Text style={styles.body}>{screen.summary}</Text>
          </Section>
        ) : null}

        {screen.red_flags.length > 0 && (
          <Section title="Red Flags">
            {screen.red_flags.map((flag, i) => (
              <View key={i} style={styles.flagRow}>
                <Ionicons name="warning" size={14} color={theme.colors.danger} />
                <Text style={[styles.body, { flex: 1 }]}>{flag}</Text>
              </View>
            ))}
          </Section>
        )}

        {screen.requirements.length > 0 && (
          <Section title="Requirements">
            {screen.requirements.map((req, i) => (
              <View key={i} style={styles.flagRow}>
                <Text style={styles.bullet}>•</Text>
                <Text style={[styles.body, { flex: 1 }]}>{req}</Text>
              </View>
            ))}
          </Section>
        )}

        {hasContact(screen) && (
          <Section title="Contact">
            {screen.contact.name ? <Text style={styles.body}>{screen.contact.name}</Text> : null}
            {screen.contact.title ? <Text style={styles.bodyMuted}>{screen.contact.title}</Text> : null}
            {screen.contact.department ? <Text style={styles.bodyMuted}>{screen.contact.department}</Text> : null}
            {screen.contact.email ? (
              <Text style={styles.link} onPress={() => Linking.openURL(`mailto:${screen.contact.email}`)}>
                {screen.contact.email}
              </Text>
            ) : null}
            {screen.contact.phone ? (
              <Text style={styles.link} onPress={() => Linking.openURL(`tel:${screen.contact.phone}`)}>
                {screen.contact.phone}
              </Text>
            ) : null}
          </Section>
        )}

        {screen.file.original_filename ? (
          <Section title="Document">
            <Text style={styles.bodyMuted}>{screen.file.original_filename}</Text>
            {screen.file.url ? (
              <Text style={styles.link} onPress={() => Linking.openURL(screen.file.url!)}>Open file</Text>
            ) : null}
          </Section>
        ) : null}

        {rescanning ? (
          <View style={styles.analyzing}>
            <ActivityIndicator color={theme.colors.primary} />
            <Text style={styles.analyzingText}>Re-analyzing…</Text>
          </View>
        ) : (
          <View style={styles.actions}>
            <Button label="Rescan" onPress={() => handleRescan(false)} variant="secondary" />
            <Button label="Rescan with New File" onPress={() => handleRescan(true)} variant="secondary" />
            <Button label="Delete" onPress={confirmDelete} variant="ghost" />
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

function hasContact(s: RfpScreenDetail): boolean {
  const c = s.contact;
  return !!(c.name || c.title || c.department || c.email || c.phone);
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: theme.colors.bg },
  centered: { alignItems: 'center', justifyContent: 'center' },
  container: { padding: theme.spacing.lg, gap: theme.spacing.md },
  title: { fontSize: theme.font.sizes.xxl, fontWeight: theme.font.weights.bold, color: theme.colors.text },
  badgeRow: { flexDirection: 'row', gap: theme.spacing.sm, alignItems: 'center' },
  statusPill: { backgroundColor: theme.colors.graySoft, paddingHorizontal: theme.spacing.sm, paddingVertical: 2, borderRadius: 999 },
  statusText: { fontSize: theme.font.sizes.xs, color: theme.colors.textMuted, textTransform: 'capitalize' },
  dueRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  dueText: { fontSize: theme.font.sizes.md, fontWeight: theme.font.weights.medium },
  section: {
    backgroundColor: theme.colors.surface,
    borderRadius: theme.radius.lg,
    borderWidth: 1,
    borderColor: theme.colors.border,
    padding: theme.spacing.lg,
    gap: theme.spacing.sm,
  },
  sectionTitle: {
    fontSize: theme.font.sizes.xs,
    color: theme.colors.textMuted,
    fontWeight: theme.font.weights.semibold,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: 2,
  },
  body: { fontSize: theme.font.sizes.md, color: theme.colors.text, lineHeight: 22 },
  bodyMuted: { fontSize: theme.font.sizes.md, color: theme.colors.textMuted, lineHeight: 22 },
  flagRow: { flexDirection: 'row', gap: theme.spacing.sm, alignItems: 'flex-start' },
  bullet: { color: theme.colors.textMuted, fontSize: theme.font.sizes.md, marginTop: 1 },
  link: { color: theme.colors.primary, fontSize: theme.font.sizes.md, fontWeight: theme.font.weights.medium },
  actions: { gap: theme.spacing.sm, marginTop: theme.spacing.md },
  analyzing: {
    flexDirection: 'row', alignItems: 'center', gap: theme.spacing.sm,
    padding: theme.spacing.lg, backgroundColor: theme.colors.primarySoft, borderRadius: theme.radius.md,
  },
  analyzingText: { color: theme.colors.text, fontSize: theme.font.sizes.sm },
  error: { color: theme.colors.danger },
});
