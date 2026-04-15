import { Stack } from 'expo-router';
import { theme } from '../../../src/theme';

export default function ScreenahLayout() {
  return (
    <Stack
      screenOptions={{
        headerStyle: { backgroundColor: theme.colors.surface },
        headerTitleStyle: { color: theme.colors.text, fontWeight: theme.font.weights.semibold },
        headerTintColor: theme.colors.primary,
        headerShadowVisible: false,
      }}
    >
      <Stack.Screen name="index" options={{ title: 'Screenah' }} />
      <Stack.Screen name="new" options={{ title: 'Screen RFP', presentation: 'modal' }} />
      <Stack.Screen name="[id]" options={{ title: 'RFP Details' }} />
    </Stack>
  );
}
