import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator, Alert, Linking, Modal, Pressable, ScrollView,
  StyleSheet, Text, TextInput, View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  addCostItem, addMilestone, addScopeItem, deleteCostItem, deleteMilestone,
  deleteProposal, deleteScopeItem, getProposal, sendProposal, updateProposal,
  type CostItem, type Milestone, type ProposalDetail, type ScopeItem,
} from '../../../src/api/proposals';
import { Button } from '../../../src/components/Button';
import { theme } from '../../../src/theme';
import { formatCurrency } from '../../../src/utils/currency';
import { statusColor } from '../../../src/utils/statusColors';

const SCOPE_CATEGORIES = ['Design', 'Development', 'SEO', 'Hosting', 'Content', 'Maintenance'];

export default function ProposalPreview() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const [p, setP] = useState<ProposalDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  // Modal states
  const [scopeModal, setScopeModal] = useState(false);
  const [costModal, setCostModal] = useState(false);
  const [milestoneModal, setMilestoneModal] = useState(false);

  // Scope form
  const [sCategory, setSCategory] = useState(SCOPE_CATEGORIES[0]);
  const [sTitle, setSTitle] = useState('');
  const [sDesc, setSDesc] = useState('');

  // Cost form
  const [cDesc, setCDesc] = useState('');
  const [cQty, setCQty] = useState('1');
  const [cPrice, setCPrice] = useState('');

  // Milestone form
  const [mTitle, setMTitle] = useState('');
  const [mDesc, setMDesc] = useState('');
  const [mAmount, setMAmount] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try { setP(await getProposal(Number(id))); } catch (e: any) { setError(e?.message); } finally { setLoading(false); }
  }, [id]);

  useEffect(() => { load(); }, [load]);

  async function handleSend() {
    Alert.alert('Send Proposal?', 'This will mark the proposal as sent.', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Send', onPress: async () => { await sendProposal(Number(id)); load(); } },
    ]);
  }

  async function handleDelete() {
    Alert.alert('Delete Proposal?', 'This cannot be undone.', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Delete', style: 'destructive', onPress: async () => { await deleteProposal(Number(id)); router.back(); } },
    ]);
  }

  async function submitScope() {
    if (!sTitle.trim()) return;
    setSaving(true);
    try {
      await addScopeItem(Number(id), { category: sCategory, title: sTitle.trim(), description: sDesc.trim() || undefined });
      setScopeModal(false); setSTitle(''); setSDesc('');
      load();
    } catch (e: any) { Alert.alert('Error', e?.message); }
    finally { setSaving(false); }
  }

  async function submitCost() {
    if (!cDesc.trim() || !cPrice) return;
    setSaving(true);
    try {
      await addCostItem(Number(id), { description: cDesc.trim(), quantity: parseInt(cQty) || 1, unit_price: parseFloat(cPrice) || 0 });
      setCostModal(false); setCDesc(''); setCQty('1'); setCPrice('');
      load();
    } catch (e: any) { Alert.alert('Error', e?.message); }
    finally { setSaving(false); }
  }

  async function submitMilestone() {
    if (!mTitle.trim()) return;
    setSaving(true);
    try {
      await addMilestone(Number(id), { title: mTitle.trim(), description: mDesc.trim() || undefined, amount: parseFloat(mAmount) || 0 });
      setMilestoneModal(false); setMTitle(''); setMDesc(''); setMAmount('');
      load();
    } catch (e: any) { Alert.alert('Error', e?.message); }
    finally { setSaving(false); }
  }

  async function removeScope(itemId: number) {
    Alert.alert('Remove scope item?', '', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Remove', style: 'destructive', onPress: async () => { await deleteScopeItem(Number(id), itemId); load(); } },
    ]);
  }

  async function removeCost(itemId: number) {
    Alert.alert('Remove line item?', '', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Remove', style: 'destructive', onPress: async () => { await deleteCostItem(Number(id), itemId); load(); } },
    ]);
  }

  async function removeMilestone(itemId: number) {
    Alert.alert('Remove milestone?', '', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Remove', style: 'destructive', onPress: async () => { await deleteMilestone(Number(id), itemId); load(); } },
    ]);
  }

  if (loading) return <SafeAreaView style={[styles.safe, styles.centered]}><ActivityIndicator color={theme.colors.primary} /></SafeAreaView>;
  if (error || !p) return <SafeAreaView style={[styles.safe, styles.centered]}><Text style={styles.error}>{error ?? 'Not found.'}</Text></SafeAreaView>;

  const sc = statusColor(p.status_color);
  const scopeGroups = groupBy(p.scope_items, 'category');

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>{p.project_title}</Text>
        <Text style={styles.headerSub}>{[p.client_name, p.client_company].filter(Boolean).join(' · ')}</Text>
      </View>

      <ScrollView contentContainerStyle={styles.content}>
        {/* Status + Meta */}
        <View style={styles.metaRow}>
          <View style={[styles.statusBadge, { backgroundColor: sc.bg }]}>
            <Text style={[styles.statusText, { color: sc.fg }]}>{p.status_label}</Text>
          </View>
          <Text style={styles.metaTotal}>{formatCurrency(p.total)}</Text>
          {p.view_count > 0 && (
            <View style={styles.viewsRow}>
              <Ionicons name="eye-outline" size={14} color={theme.colors.textMuted} />
              <Text style={styles.viewsText}>{p.view_count}</Text>
            </View>
          )}
        </View>

        {p.proposal_date && (
          <Text style={styles.dateLine}>
            Proposal Date: {new Date(p.proposal_date + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
            {p.valid_until ? ` · Valid until ${new Date(p.valid_until + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}` : ''}
          </Text>
        )}

        {/* Introduction */}
        {p.introduction ? (
          <Section title="Overview">
            <Text style={styles.body}>{stripHtml(p.introduction)}</Text>
          </Section>
        ) : null}

        {/* Scope of Work */}
        <Section title="Scope of Work" action={{ label: 'Add', onPress: () => setScopeModal(true) }}>
          {p.scope_items.length === 0 ? (
            <Text style={styles.emptyHint}>No scope items yet. Tap Add to start.</Text>
          ) : (
            Object.entries(scopeGroups).map(([cat, items]) => (
              <View key={cat} style={styles.scopeGroup}>
                <Text style={styles.scopeCategory}>{cat}</Text>
                {items.map((s) => (
                  <View key={s.id} style={styles.scopeItem}>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.scopeTitle}>{s.title}</Text>
                      {s.description ? <Text style={styles.scopeDesc}>{s.description}</Text> : null}
                      {s.bullets.map((b, i) => (
                        <Text key={i} style={styles.scopeBullet}>• {b}</Text>
                      ))}
                    </View>
                    <Pressable onPress={() => removeScope(s.id)} hitSlop={8}>
                      <Ionicons name="close-circle" size={18} color={theme.colors.textSubtle} />
                    </Pressable>
                  </View>
                ))}
              </View>
            ))
          )}
        </Section>

        {/* Investment */}
        <Section title="Investment" action={{ label: 'Add', onPress: () => setCostModal(true) }}>
          {p.cost_items.length === 0 ? (
            <Text style={styles.emptyHint}>No line items yet.</Text>
          ) : (
            <>
              {p.cost_items.map((c) => (
                <View key={c.id} style={styles.costRow}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.body}>{c.description}</Text>
                    <Text style={styles.costMeta}>{c.quantity} × {formatCurrency(c.unit_price)}</Text>
                  </View>
                  <Text style={styles.costAmount}>{formatCurrency(c.amount)}</Text>
                  <Pressable onPress={() => removeCost(c.id)} hitSlop={8}>
                    <Ionicons name="close-circle" size={18} color={theme.colors.textSubtle} />
                  </Pressable>
                </View>
              ))}
              <View style={styles.totalRow}>
                {p.discount_enabled && p.discount_amount > 0 && (
                  <>
                    <View style={styles.totalLine}>
                      <Text style={styles.totalLabel}>Subtotal</Text>
                      <Text style={styles.totalValue}>{formatCurrency(p.subtotal)}</Text>
                    </View>
                    <View style={styles.totalLine}>
                      <Text style={[styles.totalLabel, { color: theme.colors.success }]}>Discount</Text>
                      <Text style={[styles.totalValue, { color: theme.colors.success }]}>-{formatCurrency(p.discount_amount)}</Text>
                    </View>
                  </>
                )}
                <View style={styles.totalLine}>
                  <Text style={[styles.totalLabel, styles.grandTotal]}>Total</Text>
                  <Text style={[styles.totalValue, styles.grandTotal]}>{formatCurrency(p.total)}</Text>
                </View>
              </View>
            </>
          )}
        </Section>

        {/* Milestones */}
        <Section title="Milestones" action={{ label: 'Add', onPress: () => setMilestoneModal(true) }}>
          {p.milestones.length === 0 ? (
            <Text style={styles.emptyHint}>No milestones yet.</Text>
          ) : (
            p.milestones.map((m) => (
              <View key={m.id} style={styles.milestoneRow}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.body}>{m.title}</Text>
                  {m.description ? <Text style={styles.costMeta}>{m.description}</Text> : null}
                  {m.due_description ? <Text style={styles.costMeta}>{m.due_description}</Text> : null}
                </View>
                <Text style={styles.costAmount}>{formatCurrency(m.amount)}</Text>
                <Pressable onPress={() => removeMilestone(m.id)} hitSlop={8}>
                  <Ionicons name="close-circle" size={18} color={theme.colors.textSubtle} />
                </Pressable>
              </View>
            ))
          )}
        </Section>

        {/* Terms */}
        {p.terms.length > 0 && (
          <Section title="Terms & Conditions">
            {p.terms.map((t, i) => (
              <Text key={t.id} style={styles.termText}>{i + 1}. {stripHtml(t.content)}</Text>
            ))}
          </Section>
        )}

        {/* Actions */}
        <View style={styles.actions}>
          {p.public_url && (
            <Pressable style={[styles.actionBtn, { backgroundColor: theme.colors.primary }]} onPress={() => Linking.openURL(p.public_url!)}>
              <Ionicons name="open-outline" size={18} color="#fff" />
              <Text style={[styles.actionLabel, { color: '#fff' }]}>Preview in Browser</Text>
            </Pressable>
          )}
          {p.status === 'draft' && (
            <Pressable style={[styles.actionBtn, { backgroundColor: theme.colors.success }]} onPress={handleSend}>
              <Ionicons name="paper-plane-outline" size={18} color="#fff" />
              <Text style={[styles.actionLabel, { color: '#fff' }]}>Send Proposal</Text>
            </Pressable>
          )}
          <Pressable style={[styles.actionBtn, styles.deleteBtn]} onPress={handleDelete}>
            <Ionicons name="trash-outline" size={18} color={theme.colors.danger} />
            <Text style={[styles.actionLabel, { color: theme.colors.danger }]}>Delete</Text>
          </Pressable>
        </View>
      </ScrollView>

      {/* Add Scope Modal */}
      <FormModal visible={scopeModal} title="Add Scope Item" onClose={() => setScopeModal(false)} onSubmit={submitScope} saving={saving}>
        <Text style={styles.fieldLabel}>Category</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipScroll}>
          {SCOPE_CATEGORIES.map((c) => (
            <Pressable key={c} onPress={() => setSCategory(c)} style={[styles.chip, sCategory === c && styles.chipActive]}>
              <Text style={[styles.chipText, sCategory === c && styles.chipTextActive]}>{c}</Text>
            </Pressable>
          ))}
        </ScrollView>
        <FieldInput label="Title *" value={sTitle} onChangeText={setSTitle} placeholder="Scope item title" />
        <FieldInput label="Description" value={sDesc} onChangeText={setSDesc} placeholder="Optional description" multiline />
      </FormModal>

      {/* Add Cost Modal */}
      <FormModal visible={costModal} title="Add Line Item" onClose={() => setCostModal(false)} onSubmit={submitCost} saving={saving}>
        <FieldInput label="Description *" value={cDesc} onChangeText={setCDesc} placeholder="Service or item" />
        <View style={styles.row}>
          <View style={{ flex: 1 }}><FieldInput label="Qty" value={cQty} onChangeText={setCQty} keyboardType="number-pad" /></View>
          <View style={{ flex: 2 }}><FieldInput label="Unit Price ($) *" value={cPrice} onChangeText={setCPrice} keyboardType="decimal-pad" placeholder="0.00" /></View>
        </View>
      </FormModal>

      {/* Add Milestone Modal */}
      <FormModal visible={milestoneModal} title="Add Milestone" onClose={() => setMilestoneModal(false)} onSubmit={submitMilestone} saving={saving}>
        <FieldInput label="Title *" value={mTitle} onChangeText={setMTitle} placeholder="e.g. Project Kickoff" />
        <FieldInput label="Description" value={mDesc} onChangeText={setMDesc} placeholder="Optional" />
        <FieldInput label="Amount ($)" value={mAmount} onChangeText={setMAmount} keyboardType="decimal-pad" placeholder="0.00" />
      </FormModal>
    </SafeAreaView>
  );
}

// --- Helpers ---

function stripHtml(html: string): string {
  return html.replace(/<[^>]*>/g, '').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#39;/g, "'").trim();
}

function groupBy<T>(arr: T[], key: keyof T): Record<string, T[]> {
  const result: Record<string, T[]> = {};
  for (const item of arr) {
    const k = String(item[key]);
    (result[k] ??= []).push(item);
  }
  return result;
}

function Section({ title, children, action }: { title: string; children: React.ReactNode; action?: { label: string; onPress: () => void } }) {
  return (
    <View style={styles.section}>
      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>{title}</Text>
        {action && (
          <Pressable onPress={action.onPress} style={styles.sectionAction}>
            <Ionicons name="add-circle-outline" size={16} color={theme.colors.primary} />
            <Text style={styles.sectionActionText}>{action.label}</Text>
          </Pressable>
        )}
      </View>
      {children}
    </View>
  );
}

function FormModal({ visible, title, onClose, onSubmit, saving, children }: {
  visible: boolean; title: string; onClose: () => void; onSubmit: () => void; saving: boolean; children: React.ReactNode;
}) {
  return (
    <Modal visible={visible} transparent animationType="slide">
      <View style={styles.modalOverlay}>
        <View style={styles.modalCard}>
          <Text style={styles.modalTitle}>{title}</Text>
          {children}
          <View style={styles.modalActions}>
            <Button label="Cancel" variant="ghost" onPress={onClose} />
            <Button label="Add" onPress={onSubmit} loading={saving} />
          </View>
        </View>
      </View>
    </Modal>
  );
}

function FieldInput(props: { label: string; value: string; onChangeText: (t: string) => void; placeholder?: string; keyboardType?: any; multiline?: boolean }) {
  return (
    <View style={styles.fieldWrap}>
      <Text style={styles.fieldLabel}>{props.label}</Text>
      <TextInput
        style={[styles.fieldInput, props.multiline && { height: 80, textAlignVertical: 'top' }]}
        value={props.value}
        onChangeText={props.onChangeText}
        placeholder={props.placeholder}
        placeholderTextColor={theme.colors.textSubtle}
        keyboardType={props.keyboardType}
        multiline={props.multiline}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: theme.colors.bg },
  centered: { alignItems: 'center', justifyContent: 'center' },
  error: { color: theme.colors.danger },
  header: { paddingHorizontal: theme.spacing.lg, paddingTop: theme.spacing.lg, paddingBottom: theme.spacing.md },
  headerTitle: { fontSize: theme.font.sizes.xxl, fontWeight: theme.font.weights.bold, color: theme.colors.text },
  headerSub: { color: theme.colors.textMuted, fontSize: theme.font.sizes.sm, marginTop: 2 },
  content: { padding: theme.spacing.lg, paddingTop: 0, gap: theme.spacing.md, paddingBottom: theme.spacing.xxl },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: theme.spacing.sm, flexWrap: 'wrap' },
  statusBadge: { paddingHorizontal: theme.spacing.sm, paddingVertical: 3, borderRadius: 999 },
  statusText: { fontSize: theme.font.sizes.xs, fontWeight: theme.font.weights.semibold },
  metaTotal: { fontSize: theme.font.sizes.lg, fontWeight: theme.font.weights.bold, color: theme.colors.text },
  viewsRow: { flexDirection: 'row', alignItems: 'center', gap: 3, marginLeft: 'auto' },
  viewsText: { fontSize: theme.font.sizes.xs, color: theme.colors.textMuted },
  dateLine: { fontSize: theme.font.sizes.sm, color: theme.colors.textMuted },
  // Sections
  section: { backgroundColor: theme.colors.surface, borderWidth: 1, borderColor: theme.colors.border, borderRadius: theme.radius.lg, padding: theme.spacing.lg, gap: theme.spacing.sm },
  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  sectionTitle: { fontSize: theme.font.sizes.xs, color: theme.colors.textMuted, fontWeight: theme.font.weights.semibold, textTransform: 'uppercase', letterSpacing: 0.5 },
  sectionAction: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  sectionActionText: { fontSize: theme.font.sizes.sm, color: theme.colors.primary, fontWeight: theme.font.weights.medium },
  emptyHint: { color: theme.colors.textSubtle, fontSize: theme.font.sizes.sm, fontStyle: 'italic' },
  body: { fontSize: theme.font.sizes.md, color: theme.colors.text, lineHeight: 22 },
  // Scope
  scopeGroup: { gap: theme.spacing.sm },
  scopeCategory: { fontSize: theme.font.sizes.sm, fontWeight: theme.font.weights.semibold, color: theme.colors.primary, marginTop: theme.spacing.xs },
  scopeItem: { flexDirection: 'row', gap: theme.spacing.sm, alignItems: 'flex-start', paddingLeft: theme.spacing.sm },
  scopeTitle: { fontSize: theme.font.sizes.md, fontWeight: theme.font.weights.medium, color: theme.colors.text },
  scopeDesc: { fontSize: theme.font.sizes.sm, color: theme.colors.textMuted, marginTop: 2 },
  scopeBullet: { fontSize: theme.font.sizes.sm, color: theme.colors.textMuted, marginTop: 2, paddingLeft: theme.spacing.sm },
  // Cost
  costRow: { flexDirection: 'row', alignItems: 'center', gap: theme.spacing.sm, paddingVertical: theme.spacing.xs, borderBottomWidth: 1, borderBottomColor: theme.colors.border },
  costMeta: { fontSize: theme.font.sizes.xs, color: theme.colors.textMuted, marginTop: 2 },
  costAmount: { fontSize: theme.font.sizes.md, fontWeight: theme.font.weights.semibold, color: theme.colors.text },
  totalRow: { marginTop: theme.spacing.sm, gap: theme.spacing.xs },
  totalLine: { flexDirection: 'row', justifyContent: 'space-between' },
  totalLabel: { fontSize: theme.font.sizes.md, color: theme.colors.textMuted },
  totalValue: { fontSize: theme.font.sizes.md, color: theme.colors.text, fontWeight: theme.font.weights.medium },
  grandTotal: { fontSize: theme.font.sizes.lg, fontWeight: theme.font.weights.bold, color: theme.colors.text },
  // Milestones
  milestoneRow: { flexDirection: 'row', alignItems: 'center', gap: theme.spacing.sm, paddingVertical: theme.spacing.xs, borderBottomWidth: 1, borderBottomColor: theme.colors.border },
  // Terms
  termText: { fontSize: theme.font.sizes.sm, color: theme.colors.textMuted, lineHeight: 20, marginBottom: theme.spacing.xs },
  // Actions
  actions: { gap: theme.spacing.sm, marginTop: theme.spacing.md, paddingBottom: theme.spacing.xl },
  actionBtn: { height: 48, borderRadius: theme.radius.md, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: theme.spacing.sm, paddingHorizontal: theme.spacing.lg },
  deleteBtn: { backgroundColor: 'transparent', borderWidth: 1, borderColor: theme.colors.danger },
  actionLabel: { fontSize: theme.font.sizes.md, fontWeight: theme.font.weights.semibold },
  // Modals
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalCard: { backgroundColor: theme.colors.surface, borderTopLeftRadius: theme.radius.xl, borderTopRightRadius: theme.radius.xl, padding: theme.spacing.xl, gap: theme.spacing.md, maxHeight: '80%' },
  modalTitle: { fontSize: theme.font.sizes.xl, fontWeight: theme.font.weights.semibold, color: theme.colors.text },
  modalActions: { flexDirection: 'row', justifyContent: 'flex-end', gap: theme.spacing.sm, marginTop: theme.spacing.sm },
  // Form fields
  fieldWrap: { marginBottom: theme.spacing.sm },
  fieldLabel: { fontSize: theme.font.sizes.sm, color: theme.colors.textMuted, fontWeight: theme.font.weights.medium, marginBottom: theme.spacing.xs },
  fieldInput: { borderWidth: 1, borderColor: theme.colors.border, borderRadius: theme.radius.md, paddingHorizontal: theme.spacing.md, height: 44, fontSize: theme.font.sizes.md, color: theme.colors.text },
  row: { flexDirection: 'row', gap: theme.spacing.sm },
  // Chips
  chipScroll: { flexGrow: 0, marginBottom: theme.spacing.sm },
  chip: { paddingHorizontal: theme.spacing.md, paddingVertical: theme.spacing.sm, borderRadius: 999, borderWidth: 1, borderColor: theme.colors.border, marginRight: theme.spacing.xs },
  chipActive: { backgroundColor: theme.colors.primary, borderColor: theme.colors.primary },
  chipText: { fontSize: theme.font.sizes.sm, color: theme.colors.text },
  chipTextActive: { color: '#fff', fontWeight: theme.font.weights.semibold },
});
