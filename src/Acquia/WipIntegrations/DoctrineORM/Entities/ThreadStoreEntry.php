<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

// @codingStandardsIgnoreStart
/**
 * Defines an entity for storing threads.
 *
 * @Entity @Table(name="thread_store", options={"engine"="InnoDB"}, indexes={
 *   @Index(name="server_idx", columns={"server_id", "status"}),
 *   @Index(name="tasks_idx", columns={"wid", "status"}),
 *   @Index(name="created_idx", columns={"status", "created"}),
 * })
 */
class ThreadStoreEntry {

  // @codingStandardsIgnoreEnd
  /**
   * The sequential ID.
   *
   * @var int
   *
   * @Id @GeneratedValue @Column(type="integer", options={"unsigned"=true})
   */
  private $id;

  /**
   * The ID of the server the thread was delegated to.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true}, name="server_id")
   */
  private $serverId;

  /**
   * The Wip object ID associated with the thread.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $wid;

  /**
   * The ID of the process that handled the thread.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $pid;

  /**
   * The created timestamp.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $created;

  /**
   * The completed timestamp.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $completed;

  /**
   * The status of the thread.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $status;

  /**
   * The output of the process.
   *
   * @var string
   *
   * @Column(type="text", name="ssh_output")
   */
  private $sshOutput;

  /**
   * The SshProcess object returned by dispatching the process.
   *
   * @var string
   *
   * @Column(type="text")
   */
  private $process;

  /**
   * Gets the ID of this record.
   *
   * @return int
   *   The ID of this record.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the server ID.
   *
   * @return int
   *   The server ID.
   */
  public function getServerId() {
    return $this->serverId;
  }

  /**
   * Sets the server ID.
   *
   * @param int $server_id
   *   The server ID.
   */
  public function setServerId($server_id) {
    $this->serverId = $server_id;
  }

  /**
   * Gets the Wip object ID.
   *
   * @return int
   *   The Wip object ID.
   */
  public function getWid() {
    return $this->wid;
  }

  /**
   * Sets the Wip object ID.
   *
   * @param int $wid
   *   The Wip object ID.
   */
  public function setWid($wid) {
    $this->wid = $wid;
  }

  /**
   * Gets the process ID.
   *
   * @return int
   *   The process ID.
   */
  public function getPid() {
    return $this->pid;
  }

  /**
   * Sets the process ID.
   *
   * @param int $pid
   *   The process ID.
   */
  public function setPid($pid) {
    $this->pid = $pid;
  }

  /**
   * Gets the created timestamp.
   *
   * @return int
   *   The created timestamp.
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Sets the created timestamp.
   *
   * @param int $created
   *   The created timestamp.
   */
  public function setCreated($created) {
    $this->created = $created;
  }

  /**
   * Gets the completed timestamp.
   *
   * @return int
   *   The completed timestamp.
   */
  public function getCompleted() {
    return $this->completed;
  }

  /**
   * Sets the completed timestamp.
   *
   * @param int $completed
   *   The completed timestamp.
   */
  public function setCompleted($completed) {
    $this->completed = $completed;
  }

  /**
   * Gets the status of the thread.
   *
   * @return int
   *   The status of the thread.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Sets the status of the thread.
   *
   * @param int $status
   *   The status of the thread.
   */
  public function setStatus($status) {
    $this->status = $status;
  }

  /**
   * Gets the output of the process.
   *
   * @return string
   *   The output of the process.
   */
  public function getSshOutput() {
    return $this->sshOutput;
  }

  /**
   * Sets the output of the process.
   *
   * @param string $ssh_output
   *   The output of the process.
   */
  public function setSshOutput($ssh_output) {
    $this->sshOutput = $ssh_output;
  }

  /**
   * Gets the serialized SshProcess object returned by dispatching the process.
   *
   * @return string
   *   The serialized SshProcess object.
   */
  public function getProcess() {
    return $this->process;
  }

  /**
   * Sets the serialized SshProcess object returned by dispatching the process.
   *
   * @param string $process
   *   The serialized SshProcess object.
   */
  public function setProcess($process) {
    $this->process = $process;
  }

}
