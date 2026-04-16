import { useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Button } from './Button';
import { TextField } from './TextField';
import { theme } from '../theme';
import type { ClientFormData } from '../api/clients';

const US_STATES: Record<string, string> = {
  AL: 'Alabama', AK: 'Alaska', AZ: 'Arizona', AR: 'Arkansas', CA: 'California',
  CO: 'Colorado', CT: 'Connecticut', DE: 'Delaware', FL: 'Florida', GA: 'Georgia',
  HI: 'Hawaii', ID: 'Idaho', IL: 'Illinois', IN: 'Indiana', IA: 'Iowa',
  KS: 'Kansas', KY: 'Kentucky', LA: 'Louisiana', ME: 'Maine', MD: 'Maryland',
  MA: 'Massachusetts', MI: 'Michigan', MN: 'Minnesota', MS: 'Mississippi', MO: 'Missouri',
  MT: 'Montana', NE: 'Nebraska', NV: 'Nevada', NH: 'New Hampshire', NJ: 'New Jersey',
  NM: 'New Mexico', NY: 'New York', NC: 'North Carolina', ND: 'North Dakota', OH: 'Ohio',
  OK: 'Oklahoma', OR: 'Oregon', PA: 'Pennsylvania', RI: 'Rhode Island', SC: 'South Carolina',
  SD: 'South Dakota', TN: 'Tennessee', TX: 'Texas', UT: 'Utah', VT: 'Vermont',
  VA: 'Virginia', WA: 'Washington', WV: 'West Virginia', WI: 'Wisconsin', WY: 'Wyoming',
  DC: 'District of Columbia',
};

type InitialData = { [K in keyof ClientFormData]?: ClientFormData[K] | null };

type Props = {
  initial?: InitialData;
  onSubmit: (data: ClientFormData) => Promise<void>;
  submitLabel: string;
  onCancel: () => void;
};

export function ClientForm({ initial, onSubmit, submitLabel, onCancel }: Props) {
  const [name, setName] = useState(initial?.name ?? '');
  const [title, setTitle] = useState(initial?.title ?? '');
  const [email, setEmail] = useState(initial?.email ?? '');
  const [company, setCompany] = useState(initial?.company ?? '');
  const [phone, setPhone] = useState(initial?.phone ?? '');
  const [domain, setDomain] = useState(initial?.domain ?? '');
  const [address1, setAddress1] = useState(initial?.address1 ?? '');
  const [address2, setAddress2] = useState(initial?.address2 ?? '');
  const [city, setCity] = useState(initial?.city ?? '');
  const [state, setState] = useState(initial?.state ?? '');
  const [zip, setZip] = useState(initial?.zip ?? '');
  const [notes, setNotes] = useState(initial?.notes ?? '');
  const [showStates, setShowStates] = useState(false);
  const [stateSearch, setStateSearch] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});

  async function handleSubmit() {
    setError(null);
    setErrors({});
    setLoading(true);
    try {
      await onSubmit({
        name, title: title || undefined, email,
        company: company || undefined, phone: phone || undefined, domain: domain || undefined,
        address1: address1 || undefined, address2: address2 || undefined,
        city: city || undefined, state: state || undefined, zip: zip || undefined,
        notes: notes || undefined,
      });
    } catch (e: any) {
      if (e?.body?.errors) {
        const fieldErrors: Record<string, string> = {};
        for (const [k, v] of Object.entries(e.body.errors)) {
          fieldErrors[k] = Array.isArray(v) ? v[0] : String(v);
        }
        setErrors(fieldErrors);
      }
      setError(e?.message ?? 'Save failed.');
    } finally {
      setLoading(false);
    }
  }

  const filteredStates = Object.entries(US_STATES).filter(([code, name]) =>
    code.toLowerCase().includes(stateSearch.toLowerCase()) ||
    name.toLowerCase().includes(stateSearch.toLowerCase())
  );

  return (
    <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
        <TextField label="Name *" value={name} onChangeText={setName} error={errors.name} placeholder="Full name" autoCapitalize="words" />
        <TextField label="Title" value={title} onChangeText={setTitle} error={errors.title} placeholder="Job title" autoCapitalize="words" />
        <TextField label="Email *" value={email} onChangeText={setEmail} error={errors.email} placeholder="email@example.com" keyboardType="email-address" autoCapitalize="none" />
        <TextField label="Company" value={company} onChangeText={setCompany} error={errors.company} placeholder="Company name" autoCapitalize="words" />
        <TextField label="Phone" value={phone} onChangeText={setPhone} error={errors.phone} placeholder="(555) 123-4567" keyboardType="phone-pad" />
        <TextField label="Domain" value={domain} onChangeText={setDomain} error={errors.domain} placeholder="www.example.com" autoCapitalize="none" keyboardType="url" />

        <TextField label="Address Line 1" value={address1} onChangeText={setAddress1} error={errors.address1} placeholder="123 Main St" />
        <TextField label="Address Line 2" value={address2} onChangeText={setAddress2} error={errors.address2} placeholder="Suite 200" />
        <TextField label="City" value={city} onChangeText={setCity} error={errors.city} placeholder="City" autoCapitalize="words" />

        <View style={styles.fieldWrap}>
          <Text style={styles.fieldLabel}>State</Text>
          <Pressable style={styles.selectBtn} onPress={() => setShowStates(!showStates)}>
            <Text style={state ? styles.selectText : styles.selectPlaceholder}>
              {state ? `${US_STATES[state]} (${state})` : 'Select state'}
            </Text>
            <Ionicons name={showStates ? 'chevron-up' : 'chevron-down'} size={18} color={theme.colors.textMuted} />
          </Pressable>
          {showStates && (
            <View style={styles.stateDropdown}>
              <TextInput
                style={styles.stateSearchInput}
                placeholder="Search states…"
                placeholderTextColor={theme.colors.textSubtle}
                value={stateSearch}
                onChangeText={setStateSearch}
                autoCapitalize="none"
              />
              <ScrollView style={styles.stateList} nestedScrollEnabled>
                {filteredStates.map(([code, label]) => (
                  <Pressable key={code} style={styles.stateItem} onPress={() => { setState(code); setShowStates(false); setStateSearch(''); }}>
                    <Text style={[styles.stateItemText, state === code && { color: theme.colors.primary, fontWeight: theme.font.weights.semibold }]}>
                      {label} ({code})
                    </Text>
                  </Pressable>
                ))}
              </ScrollView>
            </View>
          )}
        </View>

        <TextField label="ZIP Code" value={zip} onChangeText={setZip} error={errors.zip} placeholder="12345" keyboardType="number-pad" />

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <View style={{ gap: theme.spacing.sm, marginTop: theme.spacing.md }}>
          <Button label={submitLabel} onPress={handleSubmit} loading={loading} />
          <Button label="Cancel" variant="ghost" onPress={onCancel} />
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

// Need TextInput imported for the state search
import { TextInput } from 'react-native';

const styles = StyleSheet.create({
  container: { padding: theme.spacing.lg },
  sectionLabel: {
    fontSize: theme.font.sizes.xs,
    color: theme.colors.textMuted,
    fontWeight: theme.font.weights.semibold,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: theme.spacing.sm,
  },
  fieldWrap: { marginBottom: theme.spacing.md },
  fieldLabel: {
    fontSize: theme.font.sizes.sm,
    fontWeight: theme.font.weights.medium,
    color: theme.colors.textMuted,
    marginBottom: theme.spacing.xs,
  },
  selectBtn: {
    height: 48,
    borderWidth: 1,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.md,
    paddingHorizontal: theme.spacing.md,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: theme.colors.surface,
  },
  selectText: { fontSize: theme.font.sizes.md, color: theme.colors.text },
  selectPlaceholder: { fontSize: theme.font.sizes.md, color: theme.colors.textSubtle },
  stateDropdown: {
    marginTop: theme.spacing.xs,
    borderWidth: 1,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.md,
    backgroundColor: theme.colors.surface,
    maxHeight: 200,
  },
  stateSearchInput: {
    height: 40,
    paddingHorizontal: theme.spacing.md,
    fontSize: theme.font.sizes.sm,
    color: theme.colors.text,
    borderBottomWidth: 1,
    borderBottomColor: theme.colors.border,
  },
  stateList: { maxHeight: 160 },
  stateItem: { paddingHorizontal: theme.spacing.md, paddingVertical: theme.spacing.sm },
  stateItemText: { fontSize: theme.font.sizes.sm, color: theme.colors.text },
  error: { color: theme.colors.danger, fontSize: theme.font.sizes.sm, marginTop: theme.spacing.sm },
});
