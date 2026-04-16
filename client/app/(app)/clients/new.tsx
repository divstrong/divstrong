import { useRouter } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { ClientForm } from '../../../src/components/ClientForm';
import { createClient } from '../../../src/api/clients';
import { theme } from '../../../src/theme';

export default function NewClient() {
  const router = useRouter();

  return (
    <SafeAreaView style={{ flex: 1, backgroundColor: theme.colors.bg }} edges={['bottom']}>
      <View style={styles.header}>
        <Text style={styles.title}>Add Client</Text>
      </View>
      <ClientForm
        submitLabel="Add Client"
        onSubmit={async (data) => {
          await createClient(data);
          router.back();
        }}
        onCancel={() => router.back()}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
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
});
