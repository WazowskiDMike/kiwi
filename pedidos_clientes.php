<?php
$server = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "kiwi";

$conn = new mysqli($server, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

session_start();
    if((isset($_SESSION["usertype"]) == "0")){
        echo "<script>alert('Apenas funcionários tem acesso a essa página!');</script>";
        echo "<script>window.location.href='login.php'</script>";
    }

    $sql_pedidos = "SELECT p.id_pedido, p.data_pedido, ip.id_produto, ip.quantidade, ip.status, pr.nome, pr.imagem, u.email AS email_cliente
    FROM pedidos p 
    JOIN itens_pedido ip ON p.id_pedido = ip.id_pedido 
    JOIN produtos pr ON ip.id_produto = pr.id
    JOIN users u ON p.email_cliente = u.email 
    ORDER BY p.data_pedido DESC";
    

$result_pedidos = $conn->query($sql_pedidos);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $id_pedido = $_POST['id_pedido'];
    $id_produto = $_POST['id_produto'];
    $new_status = $_POST['status'];

    $sql_update = "UPDATE itens_pedido SET status = ? WHERE id_pedido = ? AND id_produto = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sii", $new_status, $id_pedido, $id_produto);
    $stmt_update->execute();
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de pedidos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<style>
    .nav-logo {
        height: 4em;
        width: 10rem;
    }

    .description-container {
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .status-enviado {
        color: green;
    }

    .status-pendente {
        color: yellow;
    }

    .status-cancelado {
        color: red;
    }

    .input-group {
        width: 26rem;
    }
    .loader-container {
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background: rgba(0, 0, 0, .6);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loader {
    width: 50px;
    padding: 8px;
    aspect-ratio: 1;
    border-radius: 50%;
    background: #fff;
    --_m: 
        conic-gradient(#0000 10%,#000),
        linear-gradient(#000 0 0) content-box;
    -webkit-mask: var(--_m);
            mask: var(--_m);
    -webkit-mask-composite: source-out;
            mask-composite: subtract;
    animation: l3 2s infinite linear;
    }
    @keyframes l3 {to{transform: rotate(1turn)}}
    .slide-up {
      opacity: 0;
      transform: translateY(100px);
      transition: opacity 0.5s ease-out, transform 0.5s ease-out;
    }
    .slide-up.active {
      opacity: 1;
      transform: translateY(0);
    }
    /* Apenas para facilitar a visualização durante o scroll */
    body {
      height: 2000px;
    }
    #slideUpElement {
      margin-top: 80px; /* Ajuste conforme necessário para testar o scroll */
    }
</style>

<body>
        <div id="loader" class="loader-container">
            <div class="loader"></div> 
        </div>
    <div class="wrapper">
        <aside id="sidebar" class="js-sidebar">
            <div class="h-100">
                <ul class="sidebar-nav">
                    <li class="sidebar-header">
                        <hr>
                    </li>
                    <li class="sidebar-item">
                        <a href="dashboard.php" class="sidebar-link">
                            <i class="fa-solid fa-list pe-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="perfil.php" class="sidebar-link">
                            <i class="fa-solid fa-user"></i>
                            Perfil
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="index.php" class="sidebar-link">
                            <i class="fa-solid fa-right-from-bracket fa-rotate-180"></i>
                            Sair
                        </a>
                    </li>
                </ul>
            </div>
        </aside>
        <div class="main">
            <nav class="navbar navbar-expand px-3 border-bottom">
                <button class="btn" id="sidebar-toggle" type="button">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="navbar-collapse navbar d-flex justify-content-center">
                    <img src="image/logo2.png" class="img-fluid nav-logo" alt="Logo">
                </div>
            </nav>
            <main class="content px-3 py-2">
                <div class="container-fluid">
                    <div class="mb-3 mt-3">
                        <h4>Gerenciamento de Pedidos</h4>
                    </div>
                   
                    <!-- Tabela -->
                    <div class="card border-0 slide-up" id="slideUpElement">
                        <div class="card-header d-flex justify-content-between">
                            <h3 class="card-title">
                                Pedidos de clientes
                            </h3>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Pesquisar cliente" id="searchInput">
                                <button class="btn btn-primary" type="button" id="searchButton">Pesquisar</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table" id="productTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Imagem</th>
                                        <th scope="col">Nome</th>
                                        <th scope="col">Cliente</th>
                                        <th scope="col">Quantidade</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                    if ($result_pedidos->num_rows > 0) {
                                        while ($pedido = $result_pedidos->fetch_assoc()) {
                                            $imagem_produto = $pedido['imagem'];
                                            $nome_produto = $pedido['nome'];
                                            $email_cliente = $pedido['email_cliente'];
                                            $quantidade = $pedido['quantidade'];
                                            $status = $pedido['status'];
                                            $id_pedido = $pedido['id_pedido'];
                                            $id_produto = $pedido['id_produto'];
                                            
                                            $status_class = '';
                                            if ($status == 'Enviado') {
                                                $status_class = 'status-enviado';
                                            } elseif ($status == 'Pendente') {
                                                $status_class = 'status-pendente';
                                            } elseif ($status == 'Cancelado') {
                                                $status_class = 'status-cancelado';
                                            }

                                            echo "<tr>
                                                <td><img src='$imagem_produto' alt='$nome_produto' style='height: 50px; width: 70px'></td>
                                                <td>$nome_produto</td>
                                                <td>$email_cliente</td>
                                                <td>$quantidade</td>
                                                <td class='$status_class'>$status</td>
                                                <td>
                                                    <button class='btn btn-sm btn-primary' data-bs-toggle='modal' data-bs-target='#editStatusModal' 
                                                        data-id-pedido='$id_pedido' data-id-produto='$id_produto' data-status='$status'>
                                                        Editar
                                                    </button>
                                                </td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7'>Nenhum pedido encontrado.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <nav aria-label="Page navigation example">
                                <ul class="pagination d-flex justify-content-center" id="pagination">

                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </main>
            <a href="#" class="theme-toggle">
                <i class="fa-regular fa-moon"></i>
                <i class="fa-regular fa-sun"></i>
            </a>
        </div>
    </div>

    <!-- Modal para editar status -->
    <div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editStatusModalLabel">Editar Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_pedido" id="modalIdPedido">
                        <input type="hidden" name="id_produto" id="modalIdProduto">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="modalStatus">
                                <option value="Enviado">Enviado</option>
                                <option value="Pendente">Pendente</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Salvar alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="js/pagination.js"></script>
    <script>
         document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.getElementById('loader').style.display = 'none';
            }, 300);
        });
        document.addEventListener("DOMContentLoaded", function() {
      const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add("active");
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1 }); 

      const slideUpElement = document.getElementById("slideUpElement");
      observer.observe(slideUpElement);
    });
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.getElementById('loader').style.display = 'none';
            }, 300);
        });
         document.getElementById('searchInput').addEventListener('input', function () {
        var input, filter, table, tr, td, i, txtValue;
        input = this;
        filter = input.value.toUpperCase();
        table = document.getElementById('productTable');
        tr = table.getElementsByTagName('tr');

        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName('td')[2]; 
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }
    });

        var editStatusModal = document.getElementById('editStatusModal');
        editStatusModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var idPedido = button.getAttribute('data-id-pedido');
            var idProduto = button.getAttribute('data-id-produto');
            var status = button.getAttribute('data-status');

            var modalIdPedido = editStatusModal.querySelector('#modalIdPedido');
            var modalIdProduto = editStatusModal.querySelector('#modalIdProduto');
            var modalStatus = editStatusModal.querySelector('#modalStatus');

            modalIdPedido.value = idPedido;
            modalIdProduto.value = idProduto;
            modalStatus.value = status;
        });

        goToPage(1);
        
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.min.js"></script>
    <script src="js/dashboard.js"></script>
</body>

</html>
