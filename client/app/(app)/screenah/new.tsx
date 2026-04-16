import * as DocumentPicker from 'expo-document-picker';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, Alert, KeyboardAvoidingView, Modal, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { Button } from '../../../src/components/Button';
import { ScoreBadge } from '../../../src/components/ScoreBadge';
import { TextField } from '../../../src/components/TextField';
import { createScreen, type FilePick, type RfpScreenDetail } from '../../../src/api/screens';
import { scoreColors, theme } from '../../../src/theme';

const ACCEPT = [
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'text/plain',
  'text/csv',
  'text/markdown',
];

export default function NewScreen() {
  const router = useRouter();
  const [file, setFile] = useState<FilePick | null>(null);
  const [rfpName, setRfpName] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<RfpScreenDetail | null>(null);

  async function pickFile() {
    const res = await DocumentPicker.getDocumentAsync({ type: ACCEPT, multiple: false, copyToCacheDirectory: true });
    if (res.canceled || !res.assets?.[0]) return;
    const a = res.assets[0];
    setFile({ uri: a.uri, name: a.name, mimeType: a.mimeType ?? undefined });
  }

  async function submit() {
    if (!file) {
      Alert.alert('Select a file', 'Please pick an RFP document to screen.');
      return;
    }
    setError(null);
    setSubmitting(true);
    try {
      const r = await createScreen(file, rfpName.trim() || undefined);
      setResult(r);
    } catch (e: any) {
      setError(e?.message ?? 'Screening failed.');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={{ flex: 1 }}
      >
        <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
          <Text style={styles.heading}>Upload RFP</Text>
          <Text style={styles.help}>Accepted: PDF, DOC, DOCX, TXT, CSV, MD. Max 20MB.</Text>

          <Pressable onPress={pickFile} style={styles.dropzone}>
            <Ionicons
              name={file ? 'document-attach' : 'cloud-upload-outline'}
              size={32}
              color={file ? theme.colors.primary : theme.colors.textMuted}
            />
            <Text style={styles.dropTitle}>{file ? file.name : 'Tap to select document'}</Text>
            {file ? <Text style={styles.dropSub}>Tap to change</Text> : null}
          </Pressable>

          <TextField
            label="RFP Name (optional)"
            value={rfpName}
            onChangeText={setRfpName}
            placeholder="Will be extracted from document if left blank"
          />

          {error ? <Text style={styles.error}>{error}</Text> : null}

          {submitting ? (
            <View style={styles.analyzing}>
              <ActivityIndicator color={theme.colors.primary} />
              <Text style={styles.analyzingText}>Analyzing with Claude — this may take up to a minute…</Text>
            </View>
          ) : (
            <View style={{ gap: theme.spacing.sm }}>
              <Button label="Screen RFP" onPress={submit} />
              <Button label="Cancel" variant="ghost" onPress={() => router.back()} />
            </View>
          )}
        </ScrollView>
      </KeyboardAvoidingView>

      <Modal visible={!!result} transparent animationType="fade">
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <View style={[styles.scoreCircle, { backgroundColor: result ? scoreColors[result.score_color].bg : theme.colors.graySoft }]}>
              <Text style={[styles.scoreNum, { color: result ? scoreColors[result.score_color].fg : theme.colors.gray }]}>
                {result?.score ?? '—'}
              </Text>
              <Text style={[styles.scoreOf, { color: result ? scoreColors[result.score_color].fg : theme.colors.gray }]}>/ 100</Text>
            </View>
            <ScoreBadge score={result?.score ?? null} label={result?.score_label ?? ''} color={result?.score_color ?? 'gray'} />
            <Text style={styles.modalTitle}>{result?.rfp_name || 'RFP Screened'}</Text>
            {result?.summary ? <Text style={styles.modalSummary} numberOfLines={4}>{result.summary}</Text> : null}
            <Button
              label="View Details"
              onPress={() => {
                const id = result?.id;
                setResult(null);
                if (id) router.replace(`/(app)/screenah/${id}`);
              }}
            />
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: theme.colors.bg },
  container: { padding: theme.spacing.lg, gap: theme.spacing.md },
  heading: { fontSize: theme.font.sizes.xl, fontWeight: theme.font.weights.semibold, color: theme.colors.text },
  help: { color: theme.colors.textMuted, fontSize: theme.font.sizes.sm, marginBottom: theme.spacing.sm },
  dropzone: {
    borderWidth: 2,
    borderStyle: 'dashed',
    borderColor: theme.colors.border,
    borderRadius: theme.radius.lg,
    padding: theme.spacing.xl,
    alignItems: 'center',
    gap: theme.spacing.sm,
    backgroundColor: theme.colors.surfaceAlt,
  },
  dropTitle: { fontSize: theme.font.sizes.md, color: theme.colors.text, fontWeight: theme.font.weights.medium, textAlign: 'center' },
  dropSub: { fontSize: theme.font.sizes.sm, color: theme.colors.textMuted },
  error: { color: theme.colors.danger, fontSize: theme.font.sizes.sm },
  analyzing: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: theme.spacing.sm,
    padding: theme.spacing.lg,
    backgroundColor: theme.colors.primarySoft,
    borderRadius: theme.radius.md,
  },
  analyzingText: { flex: 1, color: theme.colors.text, fontSize: theme.font.sizes.sm },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: theme.spacing.xl,
  },
  modalCard: {
    backgroundColor: theme.colors.surface,
    borderRadius: theme.radius.xl,
    padding: theme.spacing.xl,
    width: '100%',
    alignItems: 'center',
    gap: theme.spacing.md,
  },
  scoreCircle: {
    width: 100,
    height: 100,
    borderRadius: 50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  scoreNum: {
    fontSize: 36,
    fontWeight: theme.font.weights.bold,
  },
  scoreOf: {
    fontSize: theme.font.sizes.sm,
    fontWeight: theme.font.weights.medium,
    marginTop: -4,
  },
  modalTitle: {
    fontSize: theme.font.sizes.lg,
    fontWeight: theme.font.weights.semibold,
    color: theme.colors.text,
    textAlign: 'center',
  },
  modalSummary: {
    fontSize: theme.font.sizes.sm,
    color: theme.colors.textMuted,
    textAlign: 'center',
    lineHeight: 20,
  },
});
