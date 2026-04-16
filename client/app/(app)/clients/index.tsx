import { Ionicons } from '@expo/vector-icons';
import { Link, useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { listClients, type ClientSummary } from '../../../src/api/clients';
import { theme } from '../../../src/theme';

export default function ClientsList() {
  const router = useRouter();
  const [items, setItems] = useState<ClientSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (silent = false, q?: string) => {
    if (!silent) setLoading(true);
    setError(null);
    try {
      setItems(await listClients(q));
    } catch (e: any) {
      setError(e?.message ?? 'Failed to load clients.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(false, search); }, [load]));

  function handleSearch(text: string) {
    setSearch(text);
    load(true, text);
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <View style={styles.header}>
        <Text style={styles.title}>Clients</Text>
        <Text style={styles.subtitle}>Manage your client directory</Text>
      </View>
      <View style={styles.searchWrap}>
        <Ionicons name="search" size={18} color={theme.colors.textMuted} style={styles.searchIcon} />
        <TextInput
          style={styles.searchInput}
          placeholder="Search clients…"
          placeholderTextColor={theme.colors.textSubtle}
          value={search}
          onChangeText={handleSearch}
          autoCapitalize="none"
          autoCorrect={false}
        />
        {search.length > 0 && (
          <Pressable onPress={() => handleSearch('')}>
            <Ionicons name="close-circle" size={18} color={theme.colors.textMuted} />
          </Pressable>
        )}
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
          <Ionicons name="people-outline" size={48} color={theme.colors.textSubtle} />
          <Text style={styles.emptyTitle}>{search ? 'No results' : 'No clients yet'}</Text>
          {!search && <Text style={styles.emptySub}>Tap the button below to add your first client.</Text>}
        </View>
      ) : (
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => { setRefreshing(true); load(true, search); }}
              tintColor={theme.colors.primary}
            />
          }
          renderItem={({ item }) => (
            <Pressable
              onPress={() => router.push(`/(app)/clients/${item.id}`)}
              style={({ pressed }) => [styles.card, pressed && { opacity: 0.8 }]}
            >
              <View style={styles.avatar}>
                <Text style={styles.avatarText}>
                  {(item.name || '?').charAt(0).toUpperCase()}
                </Text>
              </View>
              <View style={styles.cardBody}>
                <Text style={styles.name} numberOfLines={1}>{item.name}</Text>
                {item.company ? <Text style={styles.sub} numberOfLines={1}>{item.company}</Text> : null}
                <Text style={styles.sub} numberOfLines={1}>{item.email}</Text>
              </View>
              <View style={styles.cardRight}>
                {item.proposals_count > 0 && (
                  <View style={styles.proposalBadge}>
                    <Text style={styles.proposalCount}>{item.proposals_count}</Text>
                  </View>
                )}
                <Ionicons name="chevron-forward" size={18} color={theme.colors.textSubtle} />
              </View>
            </Pressable>
          )}
        />
      )}

      <Link href="/(app)/clients/new" asChild>
        <Pressable style={styles.fab}>
          <Ionicons name="person-add" size={20} color="#fff" />
          <Text style={styles.fabText}>Add Client</Text>
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
    paddingBottom: theme.spacing.sm,
  },
  title: {
    fontSize: theme.font.sizes.xxl,
    fontWeight: theme.font.weights.bold,
    color: theme.colors.text,
  },
  subtitle: { color: theme.colors.textMuted, fontSize: theme.font.sizes.sm, marginTop: 2 },
  searchWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    margin: theme.spacing.lg,
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
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: theme.spacing.xl },
  emptyTitle: { marginTop: theme.spacing.md, fontSize: theme.font.sizes.lg, fontWeight: theme.font.weights.semibold, color: theme.colors.text },
  emptySub: { marginTop: 4, color: theme.colors.textMuted, textAlign: 'center' },
  error: { color: theme.colors.danger, textAlign: 'center' },
  list: { paddingHorizontal: theme.spacing.lg, paddingBottom: 120, gap: theme.spacing.sm },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: theme.colors.surface,
    borderWidth: 1,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.lg,
    padding: theme.spacing.md,
    gap: theme.spacing.md,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: theme.colors.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: theme.colors.primary, fontSize: theme.font.sizes.lg, fontWeight: theme.font.weights.bold },
  cardBody: { flex: 1, gap: 2 },
  name: { fontSize: theme.font.sizes.md, fontWeight: theme.font.weights.semibold, color: theme.colors.text },
  sub: { fontSize: theme.font.sizes.sm, color: theme.colors.textMuted },
  cardRight: { flexDirection: 'row', alignItems: 'center', gap: theme.spacing.sm },
  proposalBadge: {
    backgroundColor: theme.colors.graySoft,
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 2,
  },
  proposalCount: { fontSize: theme.font.sizes.xs, color: theme.colors.textMuted, fontWeight: theme.font.weights.semibold },
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
