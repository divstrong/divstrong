import * as DocumentPicker from 'expo-document-picker';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { Button } from '../../../src/components/Button';
import { TextField } from '../../../src/components/TextField';
import { createScreen, type FilePick } from '../../../src/api/screens';
import { theme } from '../../../src/theme';

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
      const result = await createScreen(file, rfpName.trim() || undefined);
      router.replace(`/(app)/screenah/${result.id}`);
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
});
