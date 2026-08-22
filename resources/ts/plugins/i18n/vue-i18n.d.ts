/**
 * global type definitions
 * using the typescript interface, you can define the i18n resources that is type-safed!
 */

/**
 * you need to import the some interfaces
 */
import ar from '@/plugins/i18n/locales/ar.json';
import 'vue-i18n';

type LocaleMessage = typeof ar

declare module 'vue-i18n' { 
  export interface DefineLocaleMessage extends LocaleMessage {
  }
}
