import { useLocalSearchParams, useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Linking, Pressable, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { Button } from '../../../src/components/Button';
import { ClientForm } from '../../../src/components/ClientForm';
import { deleteClient, getClient, updateClient, type ClientDetail } from '../../../src/api/clients';
import { theme } from '../../../src/theme';

export default function EditClient() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const [client, setClient] = useState<ClientDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setClient(await getClient(Number(id)));
    } catch (e: any) {
      setError(e?.message ?? 'Failed to load client.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => { load(); }, [load]);

  function confirmDelete() {
    Alert.alert('Delete Client?', 'This cannot be undone.', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: async () => {
          try {
            await deleteClient(Number(id));
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

  if (error || !client) {
    return (
      <SafeAreaView style={[styles.safe, styles.centered]}>
        <Text style={styles.error}>{error ?? 'Not found.'}</Text>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="chevron-back" size={24} color={theme.colors.primary} />
          <Text style={styles.backText}>Clients</Text>
        </Pressable>
        <Text style={styles.title}>{client.name}</Text>
        {client.company ? <Text style={styles.subtitle}>{client.company}</Text> : null}
        {client.email ? (
          <Text style={styles.emailLink} onPress={() => Linking.openURL(`mailto:${client.email}`)}>
            {client.email}
          </Text>
        ) : null}
      </View>
      <ClientForm
        initial={client}
        submitLabel="Save Changes"
        onSubmit={async (data) => {
          await updateClient(Number(id), data);
          router.back();
        }}
        onCancel={() => router.back()}
      />
      <View style={styles.deleteWrap}>
        <Button label="Delete Client" variant="ghost" onPress={confirmDelete} />
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: theme.colors.bg },
  header: {
    paddingHorizontal: theme.spacing.lg,
    paddingTop: theme.spacing.sm,
    paddingBottom: theme.spacing.md,
  },
  backBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: theme.spacing.sm,
    marginLeft: -6,
  },
  backText: {
    color: theme.colors.primary,
    fontSize: theme.font.sizes.md,
    fontWeight: theme.font.weights.medium,
  },
  title: {
    fontSize: theme.font.sizes.xxl,
    fontWeight: theme.font.weights.bold,
    color: theme.colors.text,
  },
  subtitle: { color: theme.colors.textMuted, fontSize: theme.font.sizes.sm, marginTop: 2 },
  emailLink: { color: theme.colors.primary, fontSize: theme.font.sizes.sm, fontWeight: theme.font.weights.medium, marginTop: 2 },
  centered: { alignItems: 'center', justifyContent: 'center' },
  error: { color: theme.colors.danger },
  deleteWrap: { paddingHorizontal: theme.spacing.lg, paddingBottom: theme.spacing.lg },
});
