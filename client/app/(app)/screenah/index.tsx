import { Ionicons } from '@expo/vector-icons';
import { Link, useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useMemo, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ScoreBadge } from '../../../src/components/ScoreBadge';
import { listScreens, type RfpScreenSummary } from '../../../src/api/screens';
import { theme } from '../../../src/theme';
import { dueDateState } from '../../../src/utils/dueDate';

type Filter = null | 'great' | 'good';

export default function ScreenahList() {
  const router = useRouter();
  const [items, setItems] = useState<RfpScreenSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<Filter>(null);
  const [search, setSearch] = useState('');

  const load = useCallback(async (silent = false) => {
    if (!silent) setLoading(true);
    setError(null);
    try {
      setItems(await listScreens());
    } catch (e: any) {
      setError(e?.message ?? 'Failed to load RFPs.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  const greatCount = useMemo(() => items.filter((i) => i.score !== null && i.score >= 85).length, [items]);
  const goodCount = useMemo(() => items.filter((i) => i.score !== null && i.score >= 75).length, [items]);

  const filtered = useMemo(() => {
    let result = items;
    if (filter === 'great') result = result.filter((i) => i.score !== null && i.score >= 85);
    else if (filter === 'good') result = result.filter((i) => i.score !== null && i.score >= 75);
    if (search.trim()) {
      const q = search.toLowerCase();
      result = result.filter((i) =>
        (i.rfp_name ?? '').toLowerCase().includes(q)
      );
    }
    return result;
  }, [items, filter, search]);

  function toggleFilter(f: Filter) {
    setFilter((prev) => (prev === f ? null : f));
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <View style={styles.header}>
        <Text style={styles.title}>Screenah</Text>
        <Text style={styles.subtitle}>RFP screening</Text>
      </View>

      {!loading && items.length > 0 && (
        <>
          <View style={styles.filterRow}>
            <Pressable
              style={[styles.filterBox, filter === 'great' && styles.filterBoxActiveGreat]}
              onPress={() => toggleFilter('great')}
            >
              <Text style={[styles.filterCount, filter === 'great' && styles.filterCountActive]}>{greatCount}</Text>
              <Text style={[styles.filterLabel, filter === 'great' && styles.filterLabelActive]}>Great (85+)</Text>
            </Pressable>
            <Pressable
              style={[styles.filterBox, filter === 'good' && styles.filterBoxActiveGood]}
              onPress={() => toggleFilter('good')}
            >
              <Text style={[styles.filterCount, filter === 'good' && { color: theme.colors.warning }]}>{goodCount}</Text>
              <Text style={[styles.filterLabel, filter === 'good' && { color: theme.colors.warning }]}>Good (75+)</Text>
            </Pressable>
          </View>

          <View style={styles.searchWrap}>
            <Ionicons name="search" size={18} color={theme.colors.textMuted} style={styles.searchIcon} />
            <TextInput
              style={styles.searchInput}
              placeholder="Search by RFP title…"
              placeholderTextColor={theme.colors.textSubtle}
              value={search}
              onChangeText={setSearch}
              autoCapitalize="none"
              autoCorrect={false}
            />
            {search.length > 0 && (
              <Pressable onPress={() => setSearch('')}>
                <Ionicons name="close-circle" size={18} color={theme.colors.textMuted} />
              </Pressable>
            )}
          </View>
        </>
      )}

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
          <Ionicons name="funnel-outline" size={48} color={theme.colors.textSubtle} />
          <Text style={styles.emptyTitle}>No RFPs screened yet</Text>
          <Text style={styles.emptySub}>Tap the button below to screen your first RFP.</Text>
        </View>
      ) : filtered.length === 0 ? (
        <View style={styles.centered}>
          <Ionicons name="search" size={48} color={theme.colors.textSubtle} />
          <Text style={styles.emptyTitle}>No results</Text>
          <Text style={styles.emptySub}>Try a different search or filter.</Text>
        </View>
      ) : (
        <FlatList
          data={filtered}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => { setRefreshing(true); load(true); }}
              tintColor={theme.colors.primary}
            />
          }
          renderItem={({ item }) => (
            <Pressable
              onPress={() => router.push(`/(app)/screenah/${item.id}`)}
              style={({ pressed }) => [styles.card, pressed && { opacity: 0.8 }]}
            >
              <View style={styles.cardRow}>
                <Text style={styles.rfpName} numberOfLines={2}>
                  {item.rfp_name || 'Untitled RFP'}
                </Text>
                <Ionicons name="chevron-forward" size={20} color={theme.colors.textSubtle} />
              </View>

              <View style={styles.metaRow}>
                <ScoreBadge score={item.score} label={item.score_label} color={item.score_color} />
                {item.status !== 'completed' && (
                  <View style={styles.statusPill}>
                    <Text style={styles.statusText}>{item.status}</Text>
                  </View>
                )}
              </View>

              <View style={styles.dueRow}>
                <DueDate isoDate={item.due_date} />
              </View>
            </Pressable>
          )}
        />
      )}

      <Link href="/(app)/screenah/new" asChild>
        <Pressable style={styles.fab}>
          <Ionicons name="scan" size={20} color="#fff" />
          <Text style={styles.fabText}>Screen RFP</Text>
        </Pressable>
      </Link>
    </SafeAreaView>
  );
}

function DueDate({ isoDate }: { isoDate: string | null }) {
  const state = dueDateState(isoDate);
  return (
    <View style={styles.dueBlock}>
      <Ionicons name="calendar-outline" size={14} color={state.color} />
      <Text style={[styles.dueLabel, { color: state.color }]}>{state.label}</Text>
      {state.sub ? <Text style={[styles.dueSub, { color: state.color }]}>· {state.sub}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: theme.colors.bg },
  header: {
    paddingHorizontal: theme.spacing.lg,
    paddingTop: theme.spacing.lg,
    paddingBottom: theme.spacing.sm,
  },
  title: {
    fontSize: theme.font.sizes.xxl,
    fontWeight: theme.font.weights.bold,
    color: theme.colors.text,
  },
  subtitle: { color: theme.colors.textMuted, fontSize: theme.font.sizes.sm, marginTop: 2 },
  // Filters
  filterRow: {
    flexDirection: 'row',
    paddingHorizontal: theme.spacing.lg,
    gap: theme.spacing.sm,
    marginBottom: theme.spacing.sm,
  },
  filterBox: {
    flex: 1,
    backgroundColor: theme.colors.surface,
    borderWidth: 1,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.md,
    paddingVertical: theme.spacing.sm,
    paddingHorizontal: theme.spacing.md,
    alignItems: 'center',
  },
  filterBoxActiveGreat: {
    backgroundColor: theme.colors.successSoft,
    borderColor: theme.colors.success,
  },
  filterBoxActiveGood: {
    backgroundColor: theme.colors.warningSoft,
    borderColor: theme.colors.warning,
  },
  filterCount: {
    fontSize: theme.font.sizes.xl,
    fontWeight: theme.font.weights.bold,
    color: theme.colors.text,
  },
  filterCountActive: { color: theme.colors.success },
  filterLabel: {
    fontSize: theme.font.sizes.xs,
    color: theme.colors.textMuted,
    fontWeight: theme.font.weights.medium,
    marginTop: 2,
  },
  filterLabelActive: { color: theme.colors.success },
  // Search
  searchWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    marginHorizontal: theme.spacing.lg,
    marginBottom: theme.spacing.sm,
    borderWidth: 1,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.md,
    paddingHorizontal: theme.spacing.md,
    height: 44,
    backgroundColor: theme.colors.surface,
  },
  searchIcon: { marginRight: theme.spacing.sm },
  searchInput: {
    flex: 1,
    fontSize: theme.font.sizes.md,
    color: theme.colors.text,
    height: '100%',
  },
  // Content
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: theme.spacing.xl },
  emptyTitle: {
    marginTop: theme.spacing.md,
    fontSize: theme.font.sizes.lg,
    fontWeight: theme.font.weights.semibold,
    color: theme.colors.text,
  },
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
  cardRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: theme.spacing.sm },
  rfpName: {
    flex: 1,
    fontSize: theme.font.sizes.lg,
    fontWeight: theme.font.weights.semibold,
    color: theme.colors.text,
  },
  metaRow: { flexDirection: 'row', gap: theme.spacing.sm, alignItems: 'center', flexWrap: 'wrap' },
  statusPill: {
    backgroundColor: theme.colors.graySoft,
    paddingHorizontal: theme.spacing.sm,
    paddingVertical: 2,
    borderRadius: 999,
  },
  statusText: { fontSize: theme.font.sizes.xs, color: theme.colors.textMuted, textTransform: 'capitalize' },
  dueRow: { marginTop: 2 },
  dueBlock: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  dueLabel: { fontSize: theme.font.sizes.sm, fontWeight: theme.font.weights.medium },
  dueSub: { fontSize: theme.font.sizes.sm },
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
