import { useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { Button } from '../../../src/components/Button';
import { TextField } from '../../../src/components/TextField';
import { createProposal } from '../../../src/api/proposals';
import { listClients, type ClientSummary } from '../../../src/api/clients';
import { theme } from '../../../src/theme';

export default function NewProposal() {
  const router = useRouter();
  const [clients, setClients] = useState<ClientSummary[]>([]);
  const [selectedClient, setSelectedClient] = useState<ClientSummary | null>(null);
  const [showClients, setShowClients] = useState(false);
  const [clientSearch, setClientSearch] = useState('');

  const [projectTitle, setProjectTitle] = useState('');
  const [clientName, setClientName] = useState('');
  const [clientEmail, setClientEmail] = useState('');
  const [clientCompany, setClientCompany] = useState('');
  const [introduction, setIntroduction] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    listClients().then(setClients).catch(() => {});
  }, []);

  function selectClient(c: ClientSummary) {
    setSelectedClient(c);
    setClientName(c.name);
    setClientEmail(c.email);
    setClientCompany(c.company ?? '');
    setShowClients(false);
    setClientSearch('');
  }

  const filtered = clients.filter((c) =>
    c.name.toLowerCase().includes(clientSearch.toLowerCase()) ||
    (c.company ?? '').toLowerCase().includes(clientSearch.toLowerCase())
  );

  async function handleSubmit() {
    if (!projectTitle.trim()) { setError('Project title is required.'); return; }
    setError(null);
    setLoading(true);
    try {
      const result = await createProposal({
        client_id: selectedClient?.id,
        client_name: clientName.trim() || undefined,
        client_email: clientEmail.trim() || undefined,
        client_company: clientCompany.trim() || undefined,
        project_title: projectTitle.trim(),
        introduction: introduction.trim() || undefined,
      });
      router.replace(`/(app)/proposals/${result.id}`);
    } catch (e: any) {
      setError(e?.message ?? 'Failed to create proposal.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>New Proposal</Text>
      </View>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
          <TextField
            label="Project Title *"
            value={projectTitle}
            onChangeText={setProjectTitle}
            placeholder="e.g. Website Redesign"
            autoCapitalize="words"
          />

          {/* Client Picker */}
          <View style={styles.fieldWrap}>
            <Text style={styles.fieldLabel}>Client</Text>
            <Pressable style={styles.selectBtn} onPress={() => setShowClients(!showClients)}>
              <Text style={selectedClient ? styles.selectText : styles.selectPlaceholder}>
                {selectedClient ? `${selectedClient.name}${selectedClient.company ? ` · ${selectedClient.company}` : ''}` : 'Select existing client or enter below'}
              </Text>
              <Ionicons name={showClients ? 'chevron-up' : 'chevron-down'} size={18} color={theme.colors.textMuted} />
            </Pressable>
            {showClients && (
              <View style={styles.dropdown}>
                <View style={styles.dropdownSearch}>
                  <Ionicons name="search" size={16} color={theme.colors.textMuted} />
                  <ScrollView horizontal={false} style={{ maxHeight: 32 }}>
                    <View style={{ height: 32, justifyContent: 'center' }}>
                      <Text style={{ fontSize: theme.font.sizes.sm, color: theme.colors.textMuted }}>
                        {clientSearch || 'Search clients…'}
                      </Text>
                    </View>
                  </ScrollView>
                </View>
                <ScrollView style={styles.dropdownList} nestedScrollEnabled>
                  {filtered.map((c) => (
                    <Pressable key={c.id} style={styles.dropdownItem} onPress={() => selectClient(c)}>
                      <Text style={styles.dropdownItemText}>{c.name}</Text>
                      {c.company ? <Text style={styles.dropdownItemSub}>{c.company}</Text> : null}
                    </Pressable>
                  ))}
                  {filtered.length === 0 && (
                    <Text style={styles.dropdownEmpty}>No clients found</Text>
                  )}
                </ScrollView>
              </View>
            )}
          </View>

          <TextField label="Client Name" value={clientName} onChangeText={setClientName} placeholder="Full name" autoCapitalize="words" />
          <TextField label="Client Email" value={clientEmail} onChangeText={setClientEmail} placeholder="email@example.com" keyboardType="email-address" autoCapitalize="none" />
          <TextField label="Client Company" value={clientCompany} onChangeText={setClientCompany} placeholder="Company name" autoCapitalize="words" />

          <View style={styles.fieldWrap}>
            <Text style={styles.fieldLabel}>Introduction / Overview</Text>
            <View style={styles.textareaWrap}>
              <TextField
                label=""
                value={introduction}
                onChangeText={setIntroduction}
                placeholder="Describe the project overview…"
                multiline
                numberOfLines={6}
                style={{ height: 120, textAlignVertical: 'top', paddingTop: 12 } as any}
              />
            </View>
          </View>

          {error ? <Text style={styles.error}>{error}</Text> : null}

          <View style={{ gap: theme.spacing.sm }}>
            <Button label="Create Proposal" onPress={handleSubmit} loading={loading} />
            <Button label="Cancel" variant="ghost" onPress={() => router.back()} />
          </View>

          <Text style={styles.hint}>After creating, you can add scope items, cost breakdown, and milestones from the preview.</Text>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: theme.colors.bg },
  header: { paddingHorizontal: theme.spacing.lg, paddingTop: theme.spacing.lg, paddingBottom: theme.spacing.md },
  headerTitle: { fontSize: theme.font.sizes.xxl, fontWeight: theme.font.weights.bold, color: theme.colors.text },
  container: { padding: theme.spacing.lg, gap: theme.spacing.sm },
  fieldWrap: { marginBottom: theme.spacing.sm },
  fieldLabel: { fontSize: theme.font.sizes.sm, fontWeight: theme.font.weights.medium, color: theme.colors.textMuted, marginBottom: theme.spacing.xs },
  selectBtn: {
    height: 48, borderWidth: 1, borderColor: theme.colors.border, borderRadius: theme.radius.md,
    paddingHorizontal: theme.spacing.md, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', backgroundColor: theme.colors.surface,
  },
  selectText: { fontSize: theme.font.sizes.md, color: theme.colors.text, flex: 1 },
  selectPlaceholder: { fontSize: theme.font.sizes.md, color: theme.colors.textSubtle, flex: 1 },
  dropdown: { marginTop: theme.spacing.xs, borderWidth: 1, borderColor: theme.colors.border, borderRadius: theme.radius.md, backgroundColor: theme.colors.surface, maxHeight: 200 },
  dropdownSearch: { flexDirection: 'row', alignItems: 'center', gap: theme.spacing.sm, paddingHorizontal: theme.spacing.md, borderBottomWidth: 1, borderBottomColor: theme.colors.border, height: 40 },
  dropdownList: { maxHeight: 160 },
  dropdownItem: { paddingHorizontal: theme.spacing.md, paddingVertical: theme.spacing.sm },
  dropdownItemText: { fontSize: theme.font.sizes.sm, color: theme.colors.text, fontWeight: theme.font.weights.medium },
  dropdownItemSub: { fontSize: theme.font.sizes.xs, color: theme.colors.textMuted, marginTop: 1 },
  dropdownEmpty: { paddingHorizontal: theme.spacing.md, paddingVertical: theme.spacing.md, color: theme.colors.textSubtle, fontSize: theme.font.sizes.sm },
  textareaWrap: { marginTop: -theme.spacing.sm },
  error: { color: theme.colors.danger, fontSize: theme.font.sizes.sm },
  hint: { color: theme.colors.textSubtle, fontSize: theme.font.sizes.sm, textAlign: 'center', marginTop: theme.spacing.md, lineHeight: 20 },
});
