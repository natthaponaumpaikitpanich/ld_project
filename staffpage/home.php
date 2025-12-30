<div class="container mt-5">

  <h5 class="fw-bold mb-3">📋 งานที่ต้องทำวันนี้</h5>

  <?php foreach ($tasks as $task): ?>
    <div class="card mb-2 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <b><?= $task['customer_name'] ?></b><br>
            <small>Order: <?= $task['order_number'] ?></small>
          </div>
          <span class="badge bg-info"><?= $task['status'] ?></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  

<a href="index.php?link=Tasks">
  <button class="btn btn-success w-100 mt-2 py-3"
          data-bs-toggle="modal"
          data-bs-target="#updateStatusModal">
    🔄 Update Task Status
  </button></a>

  <!-- Map -->
  <div class="card mt-3">
    <div class="card-body">
      <h6>🗺️ Today's Route</h6>
      <div id="map" style="height:200px;"></div>
    </div>
  </div>

</div>