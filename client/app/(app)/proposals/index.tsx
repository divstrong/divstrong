import { Ionicons } from '@expo/vector-icons';
import { Link, useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { listProposals, type ProposalSummary } from '../../../src/api/proposals';
import { theme } from '../../../src/theme';
import { formatCurrency } from '../../../src/utils/currency';
import { statusColor } from '../../../src/utils/statusColors';

export default function ProposalsList() {
  const router = useRouter();
  const [items, setItems] = useState<ProposalSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (silent = false) => {
    if (!silent) setLoading(true);
    setError(null);
    try {
      setItems(await listProposals());
    } catch (e: any) {
      setError(e?.message ?? 'Failed to load proposals.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <View style={styles.header}>
        <Text style={styles.title}>Proposals</Text>
        <Text style={styles.subtitle}>Manage your proposals</Text>
      </View>

      {loading ? (
        <View style={styles.centered}>
          <ActivityIndicator color={theme.colors.primary} />
        </View>
      ) : error ? (
        <View style={styles.centered}>
          <Text style={styles.error}>{error}</Text>
        </View>
      ) : items.length === 0 ? (
        <View style={styles.centered}>
          <Ionicons name="document-text-outline" size={48} color={theme.colors.textSubtle} />
          <Text style={styles.emptyTitle}>No proposals yet</Text>
          <Text style={styles.emptySub}>Tap the button below to create your first proposal.</Text>
        </View>
      ) : (
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => { setRefreshing(true); load(true); }}
              tintColor={theme.colors.primary}
            />
          }
          renderItem={({ item }) => {
            const sc = statusColor(item.status_color);
            return (
              <Pressable
                onPress={() => router.push(`/(app)/proposals/${item.id}`)}
                style={({ pressed }) => [styles.card, pressed && { opacity: 0.8 }]}
              >
                <View style={styles.cardTop}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.projectTitle} numberOfLines={2}>{item.project_title}</Text>
                    <Text style={styles.clientName} numberOfLines={1}>
                      {[item.client_name, item.client_company].filter(Boolean).join(' · ') || 'No client'}
                    </Text>
                  </View>
                  <Ionicons name="chevron-forward" size={20} color={theme.colors.textSubtle} />
                </View>
                <View style={styles.cardBottom}>
                  <View style={[styles.statusBadge, { backgroundColor: sc.bg }]}>
                    <Text style={[styles.statusText, { color: sc.fg }]}>{item.status_label}</Text>
                  </View>
                  <Text style={styles.total}>{formatCurrency(item.total)}</Text>
                  {item.view_count > 0 && (
                    <View style={styles.viewsRow}>
                      <Ionicons name="eye-outline" size={14} color={theme.colors.textMuted} />
                      <Text style={styles.viewsText}>{item.view_count}</Text>
                    </View>
                  )}
                </View>
              </Pressable>
            );
          }}
        />
      )}

      <Link href="/(app)/proposals/new" asChild>
        <Pressable style={styles.fab}>
          <Ionicons name="add" size={22} color="#fff" />
          <Text style={styles.fabText}>New Proposal</Text>
        </Pressable>
      </Link>
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
  title: { fontSize: theme.font.sizes.xxl, fontWeight: theme.font.weights.bold, color: theme.colors.text },
  subtitle: { color: theme.colors.textMuted, fontSize: theme.font.sizes.sm, marginTop: 2 },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: theme.spacing.xl },
  emptyTitle: { marginTop: theme.spacing.md, fontSize: theme.font.sizes.lg, fontWeight: theme.font.weights.semibold, color: theme.colors.text },
  emptySub: { marginTop: 4, color: theme.colors.textMuted, textAlign: 'center' },
  error: { color: theme.colors.danger, textAlign: 'center' },
  list: { paddingHorizontal: theme.spacing.lg, paddingBottom: 120, gap: theme.spacing.sm },
  card: {
    backgroundColor: theme.colors.surface,
    borderWidth: 1,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.lg,
    padding: theme.spacing.lg,
    gap: theme.spacing.sm,
  },
  cardTop: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: theme.spacing.sm },
  projectTitle: { fontSize: theme.font.sizes.lg, fontWeight: theme.font.weights.semibold, color: theme.colors.text },
  clientName: { fontSize: theme.font.sizes.sm, color: theme.colors.textMuted, marginTop: 2 },
  cardBottom: { flexDirection: 'row', alignItems: 'center', gap: theme.spacing.sm, flexWrap: 'wrap' },
  statusBadge: { paddingHorizontal: theme.spacing.sm, paddingVertical: 3, borderRadius: 999 },
  statusText: { fontSize: theme.font.sizes.xs, fontWeight: theme.font.weights.semibold },
  total: { fontSize: theme.font.sizes.md, fontWeight: theme.font.weights.semibold, color: theme.colors.text },
  viewsRow: { flexDirection: 'row', alignItems: 'center', gap: 3, marginLeft: 'auto' },
  viewsText: { fontSize: theme.font.sizes.xs, color: theme.colors.textMuted },
  fab: {
    position: 'absolute',
    right: theme.spacing.lg,
    bottom: theme.spacing.xl,
    backgroundColor: theme.colors.primary,
    paddingHorizontal: theme.spacing.lg,
    height: 52,
    borderRadius: 999,
    flexDirection: 'row',
    alignItems: 'center',
    gap: theme.spacing.sm,
    shadowColor: '#000',
    shadowOpacity: 0.15,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 4 },
    elevation: 6,
  },
  fabText: { color: '#fff', fontWeight: theme.font.weights.semibold, fontSize: theme.font.sizes.md },
});
