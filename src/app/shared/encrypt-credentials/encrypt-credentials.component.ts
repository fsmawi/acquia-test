import {Component, OnInit, Input} from '@angular/core';
import {MdDialogRef} from '@angular/material';

import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';

@Component({
  selector: 'app-encrypt-credentials',
  templateUrl: './encrypt-credentials.component.html',
  styleUrls: ['./encrypt-credentials.component.scss']
})
export class EncryptCredentialsComponent implements OnInit {

  /**
   * Holds the application id
   * @type {string}
   */
  @Input()
  appId = '';

  /**
   * Holds the name of the environment variable
   * @type {string}
   */
  environmentVariableName = '';

  /**
   * Holds the value of environment variable to be encryoted
   * @type {string}
   */
  environmentVariableValue = '';

  /**
   * Hold the SSH key value (typed or .pem file content)
   * @type {string}
   */
  sshKeyValue = '';

  /**
   * Check if the SSH Keys Tab is selected
   * @type {boolean}
   */
  isSSHTabSelected = false;

  /**
   * Check if the environment variable encryption in progress
   * @type {boolean}
   */
  isEnvEncrypting = false;

  /**
   * Check if the SSH key encryption in progress
   * @type {boolean}
   */
  isSSHEncrypting = false;

  /**
   * Check if the SSH encryption intro is to be shown
   * @type {boolean}
   */
  isSSHIntroRequired = true;

  /**
   * Check if the environment var encryption intro is required
   * @type {boolean}
   */
  isEnvIntroRequired = true;

  /**
   * Holds the YAML content to be shown for encrypted environment variable
   */
  encryptedEnvVariableValueYAMLString: string;

  /**
   * Holds the YAML content to be shown for encrypted value
   */
  encryptedSSHKeyValueYAMLString: string;

  /**
   * Builds the component
   * @param dialogRef
   * @param errorService
   * @param pipelinesService
   */
  constructor(public dialogRef: MdDialogRef<EncryptCredentialsComponent>,
              public errorService: ErrorService,
              public pipelinesService: PipelinesService) { }

  /**
   * Initialize the component
   */
  ngOnInit() {
  }

  /**
   * Read the content of the .pem file when a new file is drag/dropped
   * @param fileList
   * @returns {File}
   */
  onFilesChange(fileList: FileList) {
    const fileReader = new FileReader();
    if (fileList.length > 0) {
      const pemFile = fileList[0];
      fileReader.onload = (e: any) => {
        this.sshKeyValue = e.target.result;
      };
      fileReader.readAsText(pemFile);
      return pemFile;
    }
  }

  /**
   * Check the tab selection
   * @param tabs
   */
  onTabSelection(tabs: any) {
    if (tabs.selectedTab.tabTitle === 'SSH Keys') {
      this.isSSHTabSelected = true;
    } else {
      this.isSSHTabSelected = false;
    }
  }

  /**
   * Encrypt the environment variable or SSK Key
   * @returns {Promise<TResult>}
   */
  encrypt() {
    const dataItem = this.isSSHTabSelected ? this.sshKeyValue : this.environmentVariableValue;

    if (this.isSSHTabSelected) {
      this.isSSHIntroRequired = false;
      this.isSSHEncrypting = true;
    } else {
      this.isEnvIntroRequired = false;
      this.isEnvEncrypting = true;
    }

    return this.pipelinesService.getEncryptedValue(this.appId, dataItem)
      .then((res: string) => {
        const encryptedValueYAMLString = this.isSSHTabSelected ? `write-key:\n  secure: ${res}` :
          `${this.environmentVariableName}:\n  secure: ${res}`;
        if (this.isSSHTabSelected) {
          this.encryptedSSHKeyValueYAMLString =  encryptedValueYAMLString;
        } else {
          this.encryptedEnvVariableValueYAMLString = encryptedValueYAMLString;
        }
        return encryptedValueYAMLString;
      })
      .catch(e =>
        this.errorService.reportError(e, 'FailedToGetEncryptedValue', {
          component: 'encrypt-credentials',
          appId: this.appId
        }, 'error')
      )
      .then((encryptedValueYAMLString) => {
        if (this.isSSHTabSelected) {
          this.isSSHEncrypting = false;
        } else {
          this.isEnvEncrypting = false;
        }
        return encryptedValueYAMLString;
      });
  }
}
